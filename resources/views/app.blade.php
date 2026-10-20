<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'CMS Landing') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @if (request()->is('/') || request()->is('landing'))
        <!-- Instant Splash Screen for Homepage -->
        <div id="splash-screen"
            style="position: fixed; inset: 0; z-index: 99999; background: white; display: flex; align-items: center; justify-content: center; transition: opacity 0.8s ease-in-out;">
            <video autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover;">
                <source src="/assets/embed/intro.mp4" type="video/mp4">
            </video>
        </div>
    @else
        <!-- Standard Loading for other pages -->
        <div id="splash-screen"
            style="position: fixed; inset: 0; z-index: 99999; background: white; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease-in-out;">
            <div
                style="width: 50px; height: 50px; border: 4px solid #3AB0E5; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;">
            </div>
            <style>
                @keyframes spin {
                    to {
                        transform: rotate(360deg);
                    }
                }
            </style>
        </div>
    @endif

    <script>
        document.body.style.overflow = 'hidden';

        var isLoaded = false;
        var minTimePassed = false;
        var splashTimeout;

        function hideSplashScreen() {
            var splash = document.getElementById('splash-screen');
            if (splash && isLoaded && minTimePassed) {
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
                }, 400); // Faster cleanup
            }
        }

        // 1. Min Intro Time (2.5s)
        var introDuration = {{ request()->is('/') || request()->is('landing') ? 2500 : 500 }};
        setTimeout(function () {
            minTimePassed = true;
            hideSplashScreen();
        }, introDuration);

        // 2. Window Load (Assets ready)
        window.addEventListener('load', function () {
            isLoaded = true;
            hideSplashScreen();
        });

        // 3. Safety Timeout (Max 5s) - Show page anyway if loading is too slow
        splashTimeout = setTimeout(function () {
            isLoaded = true;
            minTimePassed = true;
            hideSplashScreen();
        }, 5000);
    </script>

    @inertia
</body>

</html>