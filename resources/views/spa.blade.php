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
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ asset('fonts/pinyon-script-400.woff2') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400..800;1,6..72,400..700&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&family=Barlow:wght@400;500;600;700;800&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <style>
        @font-face {
            font-family: "Pinyon Script";
            src: url("{{ asset('fonts/pinyon-script-400.woff2') }}") format("woff2");
            font-weight: 400;
            font-style: normal;
            font-display: block;
        }

        html {
            scrollbar-gutter: stable;
        }

        .isf-initial-shell {
            min-height: 100vh;
            background-color: #ffffff;
            color: #171717;
        }

        .isf-skel-el {
            background-color: #e5e7eb;
            border-radius: 4px;
        }

        .isf-skel-pulse {
            animation: isfPulse 1.6s ease-in-out infinite;
        }

        @keyframes isfPulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.45;
            }
        }

        .isf-skel-artikel-grid {
            display: grid;
            grid-template-columns: minmax(0, 8fr) minmax(0, 4fr);
            gap: 32px;
        }

        .isf-skel-member-layout {
            display: flex;
            gap: 24px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 20px;
        }

        .isf-skel-member-sidebar {
            width: 224px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border-right: 1px solid #e5e7eb;
            padding-right: 16px;
        }

        @media (max-width: 1024px) {

            .isf-skel-artikel-grid,
            .isf-skel-grid {
                grid-template-columns: 1fr !important;
            }

            .isf-skel-member-layout {
                flex-direction: column !important;
            }

            .isf-skel-member-sidebar {
                width: 100% !important;
                border-right: none !important;
                border-bottom: 1px solid #e5e7eb !important;
                padding-bottom: 16px !important;
            }

            .isf-skel-nav,
            .isf-skel-portal {
                display: none !important;
            }

            .isf-skel-hero {
                height: 260px !important;
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
        <div class="isf-initial-shell isf-theme">
            @if(request()->is('anggota*'))
                @include('partials.isf-member-masthead')
                <div class="isf-skel-member-layout isf-skel-pulse" aria-busy="true">
                    <aside class="isf-skel-member-sidebar">
                        <div class="isf-skel-el" style="width: 100%; height: 36px;"></div>
                        <div class="isf-skel-el" style="width: 85%; height: 36px;"></div>
                        <div class="isf-skel-el" style="width: 90%; height: 36px;"></div>
                        <div class="isf-skel-el" style="width: 75%; height: 36px;"></div>
                    </aside>
                    <main style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                        <div>
                            <div class="isf-skel-el" style="width: 140px; height: 16px; margin-bottom: 8px;"></div>
                            <div class="isf-skel-el" style="width: 320px; height: 32px;"></div>
                        </div>
                        <div
                            style="display: flex; gap: 16px; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; padding: 16px 0;">
                            <div class="isf-skel-el" style="flex: 1; height: 40px;"></div>
                            <div class="isf-skel-el" style="width: 220px; height: 40px;"></div>
                        </div>
                        <div class="isf-skel-el" style="width: 160px; height: 14px;"></div>
                        <div style="border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden;">
                            <div
                                style="background: #f9fafb; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; display: flex; gap: 16px;">
                                <div class="isf-skel-el" style="width: 80px; height: 16px;"></div>
                                <div class="isf-skel-el" style="width: 200px; height: 16px;"></div>
                                <div class="isf-skel-el" style="width: 100px; height: 16px; margin-left: auto;"></div>
                            </div>
                            @for ($i = 0; $i < 5; $i++)
                                <div
                                    style="padding: 14px 16px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 16px;">
                                    <div class="isf-skel-el" style="width: 90px; height: 22px; background: #fee2e2;"></div>
                                    <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                        <div class="isf-skel-el" style="width: 85%; height: 16px;"></div>
                                        <div class="isf-skel-el" style="width: 35%; height: 12px;"></div>
                                    </div>
                                    <div class="isf-skel-el" style="width: 80px; height: 14px;"></div>
                                </div>
                            @endfor
                        </div>
                    </main>
                </div>
            @elseif(request()->is('isu/*') || request()->is('tokoh/*'))
                @include('partials.isf-masthead')
                <main style="max-width: 1152px; margin: 0 auto; padding: 32px 20px;" class="isf-skel-pulse"
                    aria-busy="true">
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                        <div class="isf-skel-el" style="width: 110px; height: 16px; background: #fca5a5;"></div>
                        <div class="isf-skel-el" style="width: 80%; height: 38px;"></div>
                        <div style="display: flex; align-items: center; gap: 12px; padding-top: 8px;">
                            <div class="isf-skel-el" style="width: 32px; height: 32px; border-radius: 50%;"></div>
                            <div class="isf-skel-el" style="width: 140px; height: 14px;"></div>
                            <div class="isf-skel-el" style="width: 90px; height: 14px;"></div>
                        </div>
                    </div>
                    <div class="isf-skel-el" style="width: 100%; height: 360px; border-radius: 6px; margin-bottom: 32px;">
                    </div>
                    <div class="isf-skel-artikel-grid">
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <div class="isf-skel-el" style="width: 100%; height: 16px;"></div>
                            <div class="isf-skel-el" style="width: 95%; height: 16px;"></div>
                            <div class="isf-skel-el" style="width: 90%; height: 16px;"></div>
                            <div
                                style="border-left: 4px solid #dc2626; background: #fafafa; padding: 16px; margin: 12px 0;">
                                <div class="isf-skel-el" style="width: 85%; height: 16px; margin-bottom: 10px;"></div>
                                <div class="isf-skel-el" style="width: 70%; height: 16px;"></div>
                            </div>
                            <div class="isf-skel-el" style="width: 100%; height: 16px;"></div>
                            <div class="isf-skel-el" style="width: 92%; height: 16px;"></div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px;">
                                <div class="isf-skel-el" style="width: 120px; height: 16px; margin-bottom: 14px;"></div>
                                @for ($j = 0; $j < 3; $j++)
                                    <div style="margin-bottom: 10px;">
                                        <div class="isf-skel-el"
                                            style="width: 70px; height: 12px; background: #fee2e2; margin-bottom: 6px;"></div>
                                        <div class="isf-skel-el" style="width: 95%; height: 14px;"></div>
                                </div>@endfor
                            </div>
                        </div>
                    </div>
                </main>
            @else
                @include('partials.isf-masthead')
                <main style="max-width: 1400px; margin: 0 auto; padding: 16px 20px;" class="isf-skel-pulse"
                    aria-busy="true">
                    <div
                        style="display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 18px;">
                        <div class="isf-skel-el" style="width: 72px; height: 18px; background: #fca5a5;"></div>
                        <div class="isf-skel-el" style="width: 45%; height: 16px;"></div>
                        <div class="isf-skel-el" style="width: 90px; height: 14px; margin-left: auto;"></div>
                    </div>
                    <div class="isf-skel-el isf-skel-hero"
                        style="width: 100%; height: 380px; border-radius: 6px; margin-bottom: 24px; padding: 24px; display: flex; align-items: flex-end;">
                        <div style="background: rgba(255,255,255,.95); padding: 20px; width: 480px; max-width: 100%;">
                            <div class="isf-skel-el"
                                style="width: 80px; height: 12px; background: #fca5a5; margin-bottom: 12px;"></div>
                            <div class="isf-skel-el" style="width: 90%; height: 22px; margin-bottom: 10px;"></div>
                            <div class="isf-skel-el" style="width: 100%; height: 14px; margin-bottom: 6px;"></div>
                            <div class="isf-skel-el" style="width: 70%; height: 14px;"></div>
                        </div>
                    </div>
                    <div class="isf-skel-grid"
                        style="display: grid; grid-template-columns: minmax(0,23fr) minmax(0,54fr) minmax(0,23fr); gap: 24px; border: 1px solid #e5e7eb; padding: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <div class="isf-skel-el" style="width: 110px; height: 16px;"></div>
                            <div class="isf-skel-el" style="width: 100%; height: 130px;"></div>
                            <div class="isf-skel-el" style="width: 90%; height: 14px;"></div>
                            <div class="isf-skel-el" style="width: 75%; height: 14px;"></div>
                        </div>
                        <div class="isf-skel-col-border"
                            style="display: flex; flex-direction: column; gap: 16px; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; padding: 0 20px;">
                            <div class="isf-skel-el" style="width: 80px; height: 14px; background: #fca5a5;"></div>
                            <div class="isf-skel-el" style="width: 85%; height: 26px;"></div>
                            <div class="isf-skel-el" style="width: 100%; height: 14px;"></div>
                            <div class="isf-skel-el" style="width: 95%; height: 14px;"></div>
                            <div class="isf-skel-el" style="width: 100%; height: 100px;"></div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <div class="isf-skel-el" style="width: 120px; height: 16px;"></div>
                            <div class="isf-skel-el" style="width: 100%; height: 140px;"></div>
                            <div class="isf-skel-el" style="width: 85%; height: 14px;"></div>
                        </div>
                    </div>
                </main>
            @endif
        </div>
    </div>
</body>

</html>