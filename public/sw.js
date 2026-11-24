/* DMS PT Indraco service worker (PROJECT_RULES_LAN_FRONTEND_OPS §6.2).
   Caches ONLY versioned static files (/css, /js, /images with ?v=<hash>) and the offline page.
   Never HTML pages, API responses, archive data or scans: those always go to the server. */
var CACHE = 'dms-static-v1';
var OFFLINE = '/offline.html';
var STATIC = /^\/(css|js|images)\//;

self.addEventListener('install', function (event) {
    event.waitUntil(caches.open(CACHE).then(function (cache) {
        return cache.addAll([OFFLINE, '/images/icon-192.png']);
    }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (event) {
    event.waitUntil(caches.keys().then(function (keys) {
        return Promise.all(keys.filter(function (key) { return key !== CACHE; }).map(function (key) { return caches.delete(key); }));
    }).then(function () { return self.clients.claim(); }));
});

self.addEventListener('fetch', function (event) {
    var request = event.request;
    if (request.method !== 'GET') { return; }
    var url = new URL(request.url);
    if (url.origin !== self.location.origin) { return; }

    if (request.mode === 'navigate') {
        // Pages always come from the server; the offline page only when it cannot be reached.
        event.respondWith(fetch(request).catch(function () { return caches.match(OFFLINE); }));
        return;
    }

    if (!STATIC.test(url.pathname) || !url.searchParams.has('v')) { return; }

    // Versioned file: the URL changes with the content, so cache-first is always correct.
    event.respondWith(caches.open(CACHE).then(function (cache) {
        return cache.match(request).then(function (hit) {
            if (hit) { return hit; }
            return fetch(request).then(function (response) {
                if (response.ok) {
                    // Drop older versions of the same file before storing the new one.
                    cache.keys().then(function (keys) {
                        keys.forEach(function (key) {
                            var old = new URL(key.url);
                            if (old.pathname === url.pathname && old.search !== url.search) { cache.delete(key); }
                        });
                    });
                    cache.put(request, response.clone());
                }
                return response;
            });
        });
    }));
});
