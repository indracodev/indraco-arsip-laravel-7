<!-- PT INDRACO DMS - 100% Offline Asset Suite (Zero External Dependency) -->
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
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
<script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>
