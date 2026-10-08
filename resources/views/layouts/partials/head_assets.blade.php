<!-- PT INDRACO DMS - 100% Offline Asset Suite (Zero External Dependency) -->
<link rel="icon" type="image/png" href="{{ asset('images/icon_indraco.png') }}">
<link rel="shortcut icon" href="{{ asset('images/icon_indraco.png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('images/icon_indraco.png') }}">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<script>
    // Ensure default theme is always 'light' (never fallback to OS system dark mode)
    (function() {
        var storedTheme = localStorage.getItem('theme');
        if (storedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
</script>
<script src="{{ asset('js/vendor/tailwindcss.js') }}"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    mono: ['JetBrains Mono', 'Consolas', 'Courier New', 'monospace'],
                }
            }
        }
    }
</script>
<script src="{{ asset('js/vendor/lucide.min.js') }}"></script>
<script src="{{ asset('js/file-uploader-preview.js') }}"></script>
<script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>

<script>
    // PT INDRACO DMS - Global Diagnostics & Error Helper
    window.dmsCopyError = function(errorText) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(errorText).then(function() {
                alert('Detail error berhasil disalin ke clipboard!');
            });
        }
    };
    window.addEventListener('unhandledrejection', function(event) {
        if (event.reason && (event.reason.name === 'AbortError' || event.reason.message === 'The user aborted a request.')) {
            return;
        }
        console.warn('DMS Global Exception Caught:', event.reason);
    });
</script>
