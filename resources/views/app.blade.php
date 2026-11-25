<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'CMS Landing') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/isf/ISF-Logo-2.png') }}?v=2">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-180x180.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0B1220">
    <meta name="application-name" content="Indonesia Strategy Forum">
    <meta name="apple-mobile-web-app-title" content="ISF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- Preload font lokal; @font-face & semua style CMS ada di resources/css/app.css -->
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('fonts/montserrat-latin.woff2') }}">
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
