/**
 * Seamless Desktop Navigation & Persistent Fullscreen Engine
 * DMS PT Indraco - Workstation Desktop Edition
 */
(function() {
    // 1. Strict F11 Disable: block F11 globally so accidental keyboard mistakes never interfere with Fullscreen
    window.addEventListener('keydown', function(e) {
        if (e.key === 'F11' || e.code === 'F11' || e.keyCode === 122) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }, true);

    // 2. Fullscreen persistence state management
    function isFsActive() {
        return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
    }

    function requestFs() {
        var docEl = document.documentElement;
        var rfs = docEl.requestFullscreen || docEl.webkitRequestFullscreen || docEl.mozRequestFullScreen || docEl.msRequestFullscreen;
        if (rfs) {
            return rfs.call(docEl);
        }
        return Promise.reject(new Error('Fullscreen not supported'));
    }

    function exitFs() {
        var efs = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;
        if (efs) {
            return efs.call(document);
        }
        return Promise.reject(new Error('Exit Fullscreen not supported'));
    }

    function updateFullscreenIcons(isFullscreen) {
        document.querySelectorAll('[data-fullscreen-icon]').forEach(function(el) {
            el.textContent = isFullscreen ? '❐' : '🗖';
        });
        // Sync Alpine component if present
        if (window.Alpine) {
            try {
                var alpineRoot = document.querySelector('[x-data]');
                if (alpineRoot && Alpine.$data(alpineRoot)) {
                    Alpine.$data(alpineRoot).isFullscreen = isFullscreen;
                }
            } catch(e) {}
        }
    }

    function checkFullscreenPersistence() {
        if (sessionStorage.getItem('app_fullscreen') === 'true' && !isFsActive()) {
            requestFs().then(function() {
                updateFullscreenIcons(true);
            }).catch(function() {});
        }
    }

    // Try immediate restore
    checkFullscreenPersistence();

    // Silently restore on first user interaction if browser policy blocked automatic restore
    window.addEventListener('pointerdown', checkFullscreenPersistence, { capture: true, passive: true });
    window.addEventListener('keydown', checkFullscreenPersistence, { capture: true, passive: true });
    window.addEventListener('click', checkFullscreenPersistence, { capture: true, passive: true });

    // Sync fullscreen change event
    ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(function(evt) {
        document.addEventListener(evt, function() {
            var active = isFsActive();
            updateFullscreenIcons(active);
            if (active) {
                sessionStorage.setItem('app_fullscreen', 'true');
            } else {
                // Only mark false if user deliberately exited, not during seamless body swap
                if (!window._isSeamlessNavigating) {
                    sessionStorage.setItem('app_fullscreen', 'false');
                }
            }
        });
    });

    // Expose global toggler
    window.toggleDesktopFullscreen = function() {
        if (!isFsActive()) {
            requestFs().then(function() {
                sessionStorage.setItem('app_fullscreen', 'true');
                updateFullscreenIcons(true);
            }).catch(function() {
                sessionStorage.setItem('app_fullscreen', 'true');
            });
        } else {
            exitFs().then(function() {
                sessionStorage.setItem('app_fullscreen', 'false');
                updateFullscreenIcons(false);
            }).catch(function() {
                sessionStorage.setItem('app_fullscreen', 'false');
            });
        }
    };

    // 3. Seamless SPA Swapper (Prevents document unload & keeps fullscreen active continuously)
    window._isSeamlessNavigating = false;

    async function seamlessNavigate(url, method, body) {
        method = method || 'GET';
        if (window._isSeamlessNavigating) return;
        window._isSeamlessNavigating = true;

        // Subtle desktop progress indicator at top edge
        var loader = document.getElementById('seamless-desktop-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'seamless-desktop-loader';
            loader.style.cssText = 'position:fixed;top:0;left:0;height:2px;background:#f59e0b;z-index:99999;transition:width 0.2s ease;width:0%;pointer-events:none;';
            document.documentElement.appendChild(loader);
        }
        loader.style.width = '35%';
        loader.style.opacity = '1';

        try {
            var options = {
                method: method,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                }
            };
            if (body) {
                options.body = body;
            }

            var response = await fetch(url, options);
            loader.style.width = '75%';
            var targetUrl = response.redirected ? response.url : url;
            var html = await response.text();

            // Push state to browser history
            if (window.location.href !== targetUrl) {
                window.history.pushState({}, '', targetUrl);
            }

            // Parse response
            var parser = new DOMParser();
            var newDoc = parser.parseFromString(html, 'text/html');

            // Update document title
            if (newDoc.title) {
                document.title = newDoc.title;
            }

            // Sync theme class on html
            if (newDoc.documentElement.classList.contains('dark')) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Sync font size and styles
            if (newDoc.documentElement.getAttribute('style')) {
                document.documentElement.setAttribute('style', newDoc.documentElement.getAttribute('style'));
            }

            // Sync CSRF token meta tag if updated
            var newCsrf = newDoc.querySelector('meta[name="csrf-token"]');
            var currentCsrf = document.querySelector('meta[name="csrf-token"]');
            if (newCsrf && currentCsrf) {
                currentCsrf.setAttribute('content', newCsrf.getAttribute('content'));
            }

            var currentBody = document.body;

            // Extract all script tags from newDoc body before swapping innerHTML
            var newScripts = Array.from(newDoc.querySelectorAll('body script'));
            newScripts.forEach(function(s) { s.remove(); });

            // Copy all attributes from new body to current body
            Array.from(currentBody.attributes).forEach(function(attr) {
                currentBody.removeAttribute(attr.name);
            });
            Array.from(newDoc.body.attributes).forEach(function(attr) {
                currentBody.setAttribute(attr.name, attr.value);
            });

            // Swap body content
            currentBody.innerHTML = newDoc.body.innerHTML;

            // Execute scripts from new body in order
            for (var i = 0; i < newScripts.length; i++) {
                var oldScript = newScripts[i];
                var s = document.createElement('script');
                Array.from(oldScript.attributes).forEach(function(attr) {
                    s.setAttribute(attr.name, attr.value);
                });
                s.textContent = oldScript.textContent;
                currentBody.appendChild(s);
            }

            // Finish loader
            loader.style.width = '100%';
            setTimeout(function() {
                if (loader) {
                    loader.style.opacity = '0';
                    setTimeout(function() { loader.style.width = '0%'; }, 200);
                }
            }, 100);

            // Re-init Lucide Icons
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }

            // Re-init Alpine.js
            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(currentBody);
            }

            // Update fullscreen icons
            updateFullscreenIcons(isFsActive());

            window.scrollTo(0, 0);
            window._isSeamlessNavigating = false;
            return true;
        } catch (err) {
            console.error('Seamless navigation failed, falling back:', err);
            if (loader) loader.style.opacity = '0';
            window._isSeamlessNavigating = false;
            if (method === 'GET') {
                window.location.href = url;
            }
            return false;
        }
    }

    window.seamlessNavigate = seamlessNavigate;

    // Handle browser Back / Forward history
    window.addEventListener('popstate', function() {
        seamlessNavigate(window.location.href);
    });

    // Intercept same-origin link clicks on top-level window
    document.addEventListener('click', function(e) {
        if (window.parent && window.parent !== window) return;

        var link = e.target.closest('a');
        if (!link || !link.href) return;

        if (link.href.startsWith('javascript:') || 
            link.getAttribute('target') === '_blank' || 
            link.hasAttribute('download') || 
            link.href.includes('#')) {
            return;
        }

        try {
            var urlObj = new URL(link.href, window.location.origin);
            if (urlObj.origin === window.location.origin) {
                e.preventDefault();
                seamlessNavigate(link.href);
            }
        } catch(ex) {}
    }, true);

    // Intercept same-origin form submits on top-level window (e.g. login form, logout form)
    document.addEventListener('submit', function(e) {
        if (window.parent && window.parent !== window) return;

        var form = e.target;
        if (!form || !form.action) return;

        if (form.getAttribute('target') === '_blank') return;

        try {
            var formUrl = new URL(form.action, window.location.origin);
            if (formUrl.origin === window.location.origin) {
                e.preventDefault();
                seamlessNavigate(form.action, (form.method || 'POST').toUpperCase(), new FormData(form));
            }
        } catch(ex) {}
    }, true);
})();
