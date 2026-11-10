<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'CMS Landing') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/isf/logo-isf.jpeg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/isf/logo-isf.jpeg') }}">
    <meta name="theme-color" content="#0B1220">

    <!-- Preload Critical Fonts (Local) -->
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('fonts/montserrat-latin.woff2') }}">
    <style>
        @font-face {
            font-family: "Montserrat";
            src: url("{{ asset('fonts/montserrat-latin.woff2') }}") format("woff2");
            font-weight: 100 900;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: "Montserrat";
            src: url("{{ asset('fonts/montserrat-italic-latin.woff2') }}") format("woff2");
            font-weight: 100 900;
            font-style: italic;
            font-display: swap;
        }
    </style>

    <!-- Dark Mode Preference Pre-init to avoid theme flash -->
    <script>
        (function () {
            try {
                var isDark = localStorage.getItem('cms-dark-mode');
                if (isDark === 'true' || (isDark === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body class="font-sans antialiased bg-background text-foreground">
    <!-- Standard loading splash (ISF navy + red spinner) -->
    <div id="splash-screen"
        style="position: fixed; inset: 0; z-index: 99999; background: #0B1220; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease-in-out;">
        <div
            style="width: 50px; height: 50px; border: 4px solid #E10600; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;">
        </div>
        <style>
            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }
        </style>
    </div>

    <script>
        document.body.style.overflow = 'hidden';

        (function () {
            var appMounted = false;
            var minTimePassed = false;
            var isHiding = false;
            var splashTimeout;

            function dismiss() {
                var splash = document.getElementById('splash-screen');
                if (splash && appMounted && minTimePassed && !isHiding) {
                    isHiding = true;
                    clearTimeout(splashTimeout); // Clear safety timeout
                    splash.style.opacity = '0';

                    // Trigger refresh IMMEDIATELY as it starts to fade
                    window.dispatchEvent(new Event('resize'));
                    if (window.gsap && window.ScrollTrigger) {
                        window.ScrollTrigger.refresh();
                    }

                    setTimeout(function () {
                        splash.style.display = 'none';
                        document.body.style.overflow = '';
                    }, 500); // Matches transition duration
                }
            }

            // Hook called by Inertia setup once page component is mounted
            window.hideSplashScreen = function () {
                appMounted = true;
                dismiss();
            };

            // 1. Min splash time (400ms) to ensure smooth transition without jitter on cached/fast loads
            var introDuration = 400;
            setTimeout(function () {
                minTimePassed = true;
                dismiss();
            }, introDuration);

            // 2. Safety Timeout (Max 6s) - Dismiss splash screen if JS error occurs
            splashTimeout = setTimeout(function () {
                appMounted = true;
                minTimePassed = true;
                dismiss();
            }, 6000);
        })();
    </script>

    @inertia
</body>

</html>
