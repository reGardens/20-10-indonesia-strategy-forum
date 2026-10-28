<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Indonesia Strategy Forum</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/isf/logo-isf.jpeg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/isf/logo-isf.jpeg') }}">
    <meta name="theme-color" content="#0B1220">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400..800;1,6..72,400..700&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&family=Pinyon+Script&family=Barlow:wght@400;500;600;700;800&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <style>
        .isf-initial-shell {
            min-height: 100vh;
            background-color: #ffffff;
            color: #171717;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .isf-skel-el {
            background-color: #e5e7eb;
            border-radius: 4px;
        }
        .isf-skel-pulse {
            animation: isfPulse 1.6s ease-in-out infinite;
        }
        @keyframes isfPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.45; }
        }
        @media (max-width: 1024px) {
            .isf-skel-grid {
                grid-template-columns: 1fr !important;
            }
            .isf-skel-nav {
                display: none !important;
            }
            .isf-skel-hero {
                height: 260px !important;
            }
            .isf-skel-portal {
                display: none !important;
            }
            .isf-skel-col-border {
                border-left: none !important;
                border-right: none !important;
                border-top: 1px solid #f3f4f6 !important;
                border-bottom: 1px solid #f3f4f6 !important;
                padding: 16px 0 !important;
            }
        }
    </style>
    @vite(['resources/js/spa.js'])
</head>

<body class="bg-white antialiased">
    <div id="app">
        <!-- Pre-rendered Static Skeleton: Renders on millisecond 0 (No blank screen) -->
        <div class="isf-initial-shell">
            <!-- Masthead Header -->
            <header style="border-bottom: 1px solid #e5e7eb; background: #ffffff;">
                <div style="max-width: 1400px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 14px 20px;">
                    <!-- Hamburger placeholder -->
                    <div style="width: 38px; height: 38px; display: flex; flex-direction: column; justify-content: center; gap: 5px;">
                        <span style="height: 2px; width: 22px; background: #171717; border-radius: 2px;"></span>
                        <span style="height: 2px; width: 22px; background: #171717; border-radius: 2px;"></span>
                        <span style="height: 2px; width: 22px; background: #171717; border-radius: 2px;"></span>
                    </div>

                    <!-- Logo + Brand Title -->
                    <div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin: 0 auto;">
                        <img src="{{ asset('images/isf/logo-isf.jpeg') }}" alt="ISF Logo" width="46" height="46" style="width: 46px; height: 46px; border-radius: 7px; object-fit: cover; border: 1px solid #e5e7eb;" />
                        <span style="font-size: 26px; font-weight: 700; font-style: italic; letter-spacing: -0.5px; color: #0a0a0a; font-family: Georgia, serif;">Indonesia Strategy Forum</span>
                    </div>

                    <!-- Portal Anggota & Search -->
                    <div style="display: flex; align-items: center; gap: 18px;">
                        <span class="isf-skel-portal" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #171717;">Portal Anggota</span>
                        <div style="width: 22px; height: 22px; border: 2px solid #171717; border-radius: 50%; opacity: 0.7;"></div>
                    </div>
                </div>

                <!-- Navigation Bar Placeholder -->
                <nav class="isf-skel-nav" style="border-top: 1px solid #f3f4f6; padding: 10px 20px; display: flex; justify-content: center; gap: 26px; font-size: 13px; font-weight: 500; color: #404040;">
                    <span>Beranda</span>
                    <span>Politik</span>
                    <span>Hukum</span>
                    <span>Kebijakan Publik</span>
                    <span>Sosok</span>
                    <span>Opini Publik</span>
                    <span>Survei</span>
                    <span style="color: #d1d5db;">|</span>
                    <span>Anatomi Isu</span>
                </nav>
            </header>

            <!-- Skeleton Content Body (Pulsing Animation) -->
            <main style="max-width: 1400px; margin: 0 auto; padding: 16px 20px;" class="isf-skel-pulse" aria-busy="true">
                <!-- Sorotan Bar Skeleton -->
                <div style="display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 18px;">
                    <div class="isf-skel-el" style="width: 72px; height: 18px; background: #fca5a5;"></div>
                    <div class="isf-skel-el" style="width: 45%; height: 16px;"></div>
                    <div class="isf-skel-el" style="width: 90px; height: 14px; margin-left: auto;"></div>
                </div>

                <!-- Hero Banner Skeleton (Aspect Ratio 21/9) -->
                <div class="isf-skel-el isf-skel-hero" style="width: 100%; height: 380px; border-radius: 6px; position: relative; margin-bottom: 24px; background: #e5e7eb; overflow: hidden; display: flex; flex-direction: column; justify-content: flex-end; padding: 24px; box-sizing: border-box;">
                    <div style="background: rgba(255, 255, 255, 0.95); padding: 20px; max-width: 480px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); box-sizing: border-box;">
                        <div class="isf-skel-el" style="width: 80px; height: 12px; background: #fca5a5; margin-bottom: 12px;"></div>
                        <div class="isf-skel-el" style="width: 90%; height: 22px; margin-bottom: 10px;"></div>
                        <div class="isf-skel-el" style="width: 100%; height: 14px; margin-bottom: 6px;"></div>
                        <div class="isf-skel-el" style="width: 70%; height: 14px;"></div>
                    </div>
                </div>

                <!-- 3 Columns Grid -->
                <div class="isf-skel-grid" style="display: grid; grid-template-columns: minmax(0, 23fr) minmax(0, 54fr) minmax(0, 23fr); gap: 24px; border: 1px solid #e5e7eb; padding: 20px; border-radius: 4px; box-sizing: border-box;">
                    <!-- Col 1: Insight & Terkini -->
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <div class="isf-skel-el" style="width: 110px; height: 16px; margin-bottom: 4px;"></div>
                        <div class="isf-skel-el" style="width: 100%; height: 130px; border-radius: 6px;"></div>
                        <div class="isf-skel-el" style="width: 90%; height: 14px;"></div>
                        <div class="isf-skel-el" style="width: 75%; height: 14px;"></div>
                        <div class="isf-skel-el" style="width: 80%; height: 14px; margin-top: 8px;"></div>
                    </div>

                    <!-- Col 2: Top News Hero -->
                    <div class="isf-skel-col-border" style="display: flex; flex-direction: column; gap: 16px; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; padding: 0 20px; box-sizing: border-box;">
                        <div class="isf-skel-el" style="width: 80px; height: 14px; background: #fca5a5;"></div>
                        <div class="isf-skel-el" style="width: 85%; height: 26px;"></div>
                        <div class="isf-skel-el" style="width: 100%; height: 14px;"></div>
                        <div class="isf-skel-el" style="width: 95%; height: 14px;"></div>
                        <div class="isf-skel-el" style="width: 65%; height: 14px;"></div>
                        <div class="isf-skel-el" style="width: 100%; height: 100px; border-radius: 6px; margin-top: 8px;"></div>
                    </div>

                    <!-- Col 3: Infografik & Fact Blocks -->
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <div class="isf-skel-el" style="width: 120px; height: 16px; margin-bottom: 4px;"></div>
                        <div class="isf-skel-el" style="width: 100%; height: 140px; border-radius: 6px;"></div>
                        <div class="isf-skel-el" style="width: 85%; height: 14px;"></div>
                        <div class="isf-skel-el" style="width: 60%; height: 14px;"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
