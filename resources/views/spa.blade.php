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
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('fonts/pinyon-script-400.woff2') }}">
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

        html,
        body {
            margin: 0;
        }

        .isf-initial-shell {
            min-height: 100vh;
            width: 100%;
            background-color: #fff;
            color: #171717;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
        }

        .isf-initial-shell,
        .isf-initial-shell *,
        .isf-initial-shell *::before,
        .isf-initial-shell *::after {
            box-sizing: border-box;
        }

        .isf-initial-shell a {
            color: inherit;
            text-decoration: none;
        }

        .isf-initial-shell .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .isf-initial-shell .isf-script {
            font-family: "Pinyon Script", Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-style: normal;
            line-height: 1;
            -webkit-text-stroke: .15px currentColor;
        }

        .isf-initial-shell .isf-masthead-icon {
            width: auto;
            height: 56px;
            max-height: 56px;
            aspect-ratio: 1 / 1;
            border: 1px solid rgb(212 212 212 / .8);
            border-radius: 7px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .isf-initial-shell header {
            background: #fff;
            color: #171717;
        }

        .isf-initial-shell header>div {
            border-bottom: 1px solid #d4d4d4;
        }

        .isf-initial-shell header:not(.border-b)>div:first-child>div {
            display: grid;
            grid-template-columns: minmax(2.5rem, 1fr) auto minmax(2.5rem, 1fr);
            align-items: center;
            gap: .75rem;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .isf-initial-shell header:not(.border-b)>div:first-child>div> :first-child {
            justify-self: start;
        }

        .isf-initial-shell header:not(.border-b)>div:first-child>div> :last-child {
            justify-self: end;
        }

        .isf-initial-shell header.border-b>div {
            display: grid;
            grid-template-columns: minmax(4.5rem, 1fr) auto minmax(4.5rem, 1fr);
            align-items: center;
            gap: .75rem;
            width: 100%;
            max-width: 1400px;
            min-height: 4.5rem;
            margin: 0 auto;
            padding: 1rem 1.5rem;
        }

        .isf-initial-shell header.border-b>div> :first-child {
            justify-self: start;
        }

        .isf-initial-shell header.border-b>div> :last-child {
            display: flex;
            align-items: center;
            justify-self: end;
            gap: .5rem;
        }

        .isf-initial-shell header .isf-wordmark {
            display: flex;
            min-width: 0;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            text-align: center;
            line-height: 1;
            letter-spacing: -.01em;
        }

        .isf-initial-shell header .isf-wordmark>span {
            display: inline-flex;
            align-items: stretch;
            justify-content: center;
            gap: .875rem;
        }

        .isf-initial-shell header .isf-wordmark .isf-script {
            font-size: 50px;
            color: #0a0a0a;
        }

        .isf-initial-shell header .inline-flex,
        .isf-initial-shell header:not(.border-b)>div:first-child>div> :last-child {
            display: inline-flex;
            align-items: center;
        }

        .isf-initial-shell header:not(.border-b)>div:first-child>div> :last-child {
            gap: 1rem;
        }

        .isf-initial-shell header>div:nth-child(2) nav {
            max-width: 1400px;
            margin: 0 auto;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 0 1.5rem;
        }

        .isf-initial-shell header>div:nth-child(2) ul {
            display: flex;
            width: max-content;
            align-items: center;
            gap: 1.25rem;
            margin: 0 auto;
            padding: .625rem 0;
            list-style: none;
            font-size: 15px;
            line-height: 1.5;
        }

        .isf-initial-shell header>div:nth-child(2) li {
            padding: 2px 0;
        }

        .isf-initial-shell header.border-b>div:first-child>div {
            min-height: 4.5rem;
            padding: 1rem 1.5rem;
            grid-template-columns: minmax(4.5rem, 1fr) auto minmax(4.5rem, 1fr);
        }

        .isf-initial-shell header.border-b>div>a.block {
            display: block;
            min-width: 0;
            text-align: center;
        }

        .isf-initial-shell header.border-b .isf-masthead-icon {
            height: 100%;
            max-height: 56px;
            border-radius: 5px;
        }

        .isf-initial-shell header.border-b .isf-wordmark>span {
            gap: .625rem;
        }

        .isf-initial-shell header.border-b .isf-wordmark .isf-script {
            font-size: 38px;
            line-height: 1.473684;
        }

        .isf-initial-shell header.border-b .isf-byline {
            display: block;
            margin-top: .25rem;
            color: #737373;
            font-size: .6875rem;
            font-weight: 500;
            letter-spacing: .1em;
            line-height: 1.35;
            text-transform: uppercase;
        }

        .isf-initial-shell svg:not([width]) {
            width: 1.25rem;
            height: 1.25rem;
            flex: none;
        }

        @media (max-width: 1023px) {
            .isf-initial-shell header:not(.border-b)>div:first-child>div {
                padding: .75rem 1rem 1.40625rem;
            }

            .isf-initial-shell header .isf-wordmark .isf-script {
                font-size: 30px;
                line-height: .666667;
            }

            .isf-initial-shell header .isf-masthead-icon {
                height: 56px;
                max-height: 56px;
            }

            .isf-initial-shell header>div:nth-child(2) nav {
                padding: 0 1rem;
            }

            .isf-initial-shell header>div:nth-child(2) ul {
                font-size: 13px;
                line-height: 1;
            }

            .isf-initial-shell header>div:nth-child(2) li {
                padding: 0;
            }

            .isf-initial-shell header.border-b>div {
                padding: .75rem 1rem;
            }

            .isf-initial-shell header.border-b .isf-wordmark .isf-script {
                max-width: 125px;
                font-size: 26px;
                line-height: 1;
            }
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
                opacity: .45;
            }
        }

        .isf-skel-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .isf-skel-article {
            max-width: 1152px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .isf-skel-grid {
            display: grid;
            grid-template-columns: minmax(0, 23fr) minmax(0, 54fr) minmax(0, 23fr);
        }

        .isf-skel-article-grid,
        .isf-skel-member-grid {
            display: grid;
            grid-template-columns: minmax(0, 8fr) minmax(0, 4fr);
            gap: 2rem;
        }

        .isf-skel-member-layout {
            display: flex;
            gap: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem 1rem;
        }

        .isf-skel-member-nav {
            width: 14rem;
            flex-shrink: 0;
            border-right: 1px solid #d1d5db;
            padding-right: 1rem;
        }

        .isf-skel-rule {
            border: 1px solid #d1d5db;
        }

        .isf-skel-card {
            border: 1px solid #d1d5db;
            padding: 1rem;
        }

        .isf-skel-lines>*+* {
            margin-top: .6rem;
        }

        @media (min-width: 640px) {
            .isf-skel-article { padding-top: 2.5rem; padding-bottom: 2.5rem; }
        }
        @media (min-width: 1024px) {
            .isf-skel-container { padding-right: 1.5rem; padding-left: 1.5rem; }
            .isf-skel-member-layout { padding-right: 1.5rem; padding-left: 1.5rem; }
        }
        @media (max-width: 1024px) {

            .isf-skel-grid,
            .isf-skel-article-grid,
            .isf-skel-member-grid {
                grid-template-columns: 1fr;
            }

            .isf-skel-member-layout {
                flex-direction: column;
            }

            .isf-skel-member-nav {
                width: auto;
                border-right: 0;
                border-bottom: 1px solid #d1d5db;
                padding: 0 0 1rem;
                display: flex;
                gap: .5rem;
                overflow: hidden;
            }

            .isf-skel-member-nav>div {
                min-width: 6rem;
            }
        }

        @media (max-width: 640px) {

            .isf-skel-container,
            .isf-skel-article,
            .isf-skel-member-layout {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
    @vite(['resources/js/spa.js'])
</head>

<body class="bg-white antialiased">
    <div id="app">
        <div class="isf-initial-shell isf-theme">
            @if (request()->is('anggota*'))
                @include('partials.isf-member-masthead')
                <div class="isf-skel-member-layout isf-skel-pulse" aria-busy="true"
                    aria-label="Memuat umpan peringatan">
                    <nav class="isf-skel-member-nav" aria-label="Memuat menu anggota">
                        @for ($i = 0; $i < 5; $i++)
                            <div class="isf-skel-el" style="height: 36px; margin-bottom: 8px;"></div>
                        @endfor
                    </nav>
                    <main style="min-width: 0; flex: 1;">
                        <div style="margin-bottom: 24px; border-bottom: 1px solid #d1d5db; padding-bottom: 16px;">
                            <div class="isf-skel-el" style="width: 130px; height: 12px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-el" style="width: min(420px, 75%); height: 40px;"></div>
                        </div>
                        <div class="isf-skel-member-grid">
                            <section style="display: flex; flex-direction: column; gap: 16px;">
                                <div
                                    style="display: grid; grid-template-columns: minmax(0, 1fr) 14rem; gap: 16px; border-top: 1px solid #d1d5db; border-bottom: 1px solid #d1d5db; padding: 16px 0;">
                                    <div>
                                        <div class="isf-skel-el" style="width: 72px; height: 12px; margin-bottom: 8px;">
                                        </div>
                                        <div class="isf-skel-el" style="height: 40px;"></div>
                                    </div>
                                    <div>
                                        <div class="isf-skel-el" style="width: 56px; height: 12px; margin-bottom: 8px;">
                                        </div>
                                        <div class="isf-skel-el" style="height: 40px;"></div>
                                    </div>
                                </div>
                                <div class="isf-skel-el" style="width: 150px; height: 14px;"></div>
                                <div class="isf-skel-rule">
                                    <div
                                        style="display: flex; gap: 16px; background: #f9fafb; border-bottom: 1px solid #d1d5db; padding: 12px;">
                                        <div class="isf-skel-el" style="width: 80px; height: 16px;"></div>
                                        <div class="isf-skel-el" style="flex: 1; height: 16px;"></div>
                                        <div class="isf-skel-el" style="width: 80px; height: 16px;"></div>
                                    </div>
                                    @for ($i = 0; $i < 5; $i++)
                                        <div
                                            style="display: flex; gap: 16px; border-bottom: 1px solid #e5e7eb; padding: 16px 12px;">
                                            <div class="isf-skel-el" style="width: 96px; height: 20px; flex-shrink: 0;">
                                            </div>
                                            <div style="flex: 1;">
                                                <div class="isf-skel-el"
                                                    style="width: 80%; height: 16px; margin-bottom: 8px;"></div>
                                                <div class="isf-skel-el" style="width: 100%; height: 12px;"></div>
                                            </div>
                                            <div class="isf-skel-el" style="width: 72px; height: 12px;"></div>
                                        </div>
                                    @endfor
                                </div>
                            </section>
                            <aside style="display: flex; flex-direction: column; gap: 24px;">
                                <div class="isf-skel-card">
                                    <div
                                        style="display: flex; gap: 16px; border-bottom: 1px solid #d1d5db; padding-bottom: 16px;">
                                        <div class="isf-skel-el" style="width: 48px; height: 48px;"></div>
                                        <div>
                                            <div class="isf-skel-el"
                                                style="width: 120px; height: 18px; margin-bottom: 8px;"></div>
                                            <div class="isf-skel-el" style="width: 96px; height: 12px;"></div>
                                        </div>
                                    </div>
                                    <div class="isf-skel-lines" style="margin-top: 16px;">
                                        <div class="isf-skel-el" style="height: 14px;"></div>
                                        <div class="isf-skel-el" style="width: 80%; height: 14px;"></div>
                                        <div class="isf-skel-el" style="width: 60%; height: 14px;"></div>
                                    </div>
                                </div>
                                <div class="isf-skel-card">
                                    <div class="isf-skel-el" style="width: 140px; height: 20px; margin-bottom: 16px;">
                                    </div>
                                    <div class="isf-skel-lines">
                                        @for ($i = 0; $i < 4; $i++)
                                            <div class="isf-skel-el" style="height: 14px;"></div>
                                        @endfor
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </main>
                </div>
            @elseif(request()->is('infografik'))
                @include('partials.isf-masthead')
                <main class="isf-skel-container isf-skel-pulse" aria-busy="true" aria-label="Memuat koleksi infografik">
                    <div
                        style="display: flex; justify-content: space-between; border-bottom: 1px solid #d1d5db; padding-bottom: 12px; margin-bottom: 24px;">
                        <div class="isf-skel-el" style="width: 130px; height: 14px;"></div>
                        <div class="isf-skel-el" style="width: 130px; height: 14px;"></div>
                    </div>
                    <header style="max-width: 768px; margin-bottom: 32px;">
                        <div class="isf-skel-el" style="width: 170px; height: 12px; margin-bottom: 12px;"></div>
                        <div class="isf-skel-el" style="width: 80%; height: 42px; margin-bottom: 16px;"></div>
                        <div class="isf-skel-el" style="height: 16px; margin-bottom: 8px;"></div>
                        <div class="isf-skel-el" style="width: 85%; height: 16px;"></div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 24px;">
                            @for ($i = 0; $i < 4; $i++)
                                <div class="isf-skel-el" style="width: 96px; height: 32px; border-radius: 999px;">
                                </div>
                            @endfor
                        </div>
                    </header>
                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px;">
                        @for ($i = 0; $i < 8; $i++)
                            <article class="isf-skel-rule">
                                <div class="isf-skel-el" style="height: 180px; border-radius: 0;"></div>
                                <div style="padding: 16px;">
                                    <div class="isf-skel-el" style="width: 80px; height: 12px; margin-bottom: 12px;">
                                    </div>
                                    <div class="isf-skel-el" style="height: 20px; margin-bottom: 8px;"></div>
                                    <div class="isf-skel-el" style="width: 80%; height: 20px; margin-bottom: 16px;">
                                    </div>
                                    <div class="isf-skel-el" style="width: 33%; height: 12px;"></div>
                                </div>
                            </article>
                        @endfor
                    </div>
                </main>
            @elseif(request()->is('isu/*'))
                @include('partials.isf-masthead')
                <main class="isf-skel-article isf-skel-pulse" aria-busy="true" aria-label="Memuat anatomi isu">
                    <div class="isf-skel-article-grid">
                        <article>
                            <div style="border-bottom: 1px solid #d1d5db; padding-bottom: 24px;">
                                <div class="isf-skel-el" style="width: 140px; height: 12px; margin-bottom: 20px;">
                                </div>
                                <div class="isf-skel-el" style="width: 80%; height: 42px; margin-bottom: 16px;"></div>
                                <div class="isf-skel-el" style="height: 16px; margin-bottom: 8px;"></div>
                                <div class="isf-skel-el" style="width: 85%; height: 16px;"></div>
                            </div>
                            @for ($i = 1; $i <= 7; $i++)
                                <section style="border-top: 1px solid #d1d5db; padding: 28px 0;">
                                    <div class="isf-skel-el" style="width: 220px; height: 26px; margin-bottom: 16px;">
                                    </div>
                                    @if ($i === 2)
                                        <div class="isf-skel-el" style="height: 300px;"></div>
                                    @elseif($i === 4)
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                                            @for ($j = 0; $j < 3; $j++)
                                                <div class="isf-skel-el" style="height: 120px;"></div>
                                            @endfor
                                        </div>
                                    @else<div class="isf-skel-lines">
                                            <div class="isf-skel-el" style="height: 16px;"></div>
                                            <div class="isf-skel-el" style="width: 90%; height: 16px;"></div>
                                            <div class="isf-skel-el" style="width: 75%; height: 16px;"></div>
                                        </div>
                                    @endif
                                </section>
                            @endfor
                            <div style="border-top: 1px solid #d1d5db; padding-top: 24px;" class="isf-skel-lines">
                                <div class="isf-skel-el" style="width: 240px; height: 24px;"></div>
                                @for ($i = 0; $i < 3; $i++)
                                    <div class="isf-skel-el" style="height: 20px;"></div>

                                @endfor
                            </div>
                        </article>
                        <aside style="border-top: 1px solid #d1d5db; padding-top: 24px;">
                            <div class="isf-skel-el" style="width: 120px; height: 24px; margin-bottom: 20px;"></div>
                            <div class="isf-skel-lines">
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="isf-skel-el" style="height: 20px;"></div>
                                @endfor
                            </div>
                            <div class="isf-skel-card" style="margin-top: 28px;">
                                <div class="isf-skel-el" style="width: 160px; height: 24px; margin-bottom: 16px;">
                                </div>
                                <div class="isf-skel-lines">
                                    <div class="isf-skel-el" style="height: 16px;"></div>
                                    <div class="isf-skel-el" style="height: 16px;"></div>
                                </div>
                            </div>
                        </aside>
                    </div>
                </main>
            @elseif(request()->is('tokoh/*'))
                @include('partials.isf-masthead')
                <main class="isf-skel-article isf-skel-pulse" aria-busy="true" aria-label="Memuat profil tokoh">
                    <div class="isf-skel-el" style="width: 120px; height: 12px; margin-bottom: 16px;"></div>
                    <div class="isf-skel-el" style="width: 55%; height: 42px; margin-bottom: 32px;"></div>
                    <div class="isf-skel-article-grid">
                        <article>
                            <section class="isf-skel-card">
                                <div style="display: grid; grid-template-columns: 160px minmax(0, 1fr); gap: 24px;">
                                    <div class="isf-skel-el" style="height: 210px;"></div>
                                    <div class="isf-skel-lines">
                                        <div class="isf-skel-el" style="width: 75%; height: 32px;"></div>
                                        <div class="isf-skel-el" style="height: 16px;"></div>
                                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                                            @for ($i = 0; $i < 4; $i++)
                                                <div class="isf-skel-el" style="height: 64px;"></div>
                                            @endfor
                                        </div>
                                        <div class="isf-skel-el" style="height: 56px;"></div>
                                        <div class="isf-skel-el" style="width: 128px; height: 36px;"></div>
                                    </div>
                                </div>
                            </section>
                            <section style="border-top: 1px solid #d1d5db; margin-top: 40px; padding-top: 20px;">
                                <div class="isf-skel-el" style="width: 270px; height: 26px; margin-bottom: 16px;">
                                </div>
                                <div class="isf-skel-rule">
                                    @for ($i = 0; $i < 5; $i++)
                                        <div class="isf-skel-el"
                                            style="height: 48px; border-radius: 0; border-bottom: 1px solid #e5e7eb;">
                                        </div>
                                    @endfor
                                </div>
                            </section>
                        </article>
                        <aside style="border-top: 1px solid #d1d5db; padding-top: 24px;">
                            <div class="isf-skel-el" style="width: 140px; height: 24px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-lines">
                                @for ($i = 0; $i < 3; $i++)
                                    <div class="isf-skel-el" style="height: 20px;"></div>
                                @endfor
                            </div>
                            <div style="border-top: 1px solid #d1d5db; margin-top: 28px; padding-top: 20px;">
                                <div class="isf-skel-el" style="width: 140px; height: 24px; margin-bottom: 16px;">
                                </div>
                                <div class="isf-skel-el" style="height: 260px;"></div>
                            </div>
                        </aside>
                    </div>
                </main>
            @else
                @include('partials.isf-masthead')
                <main class="isf-skel-container isf-skel-pulse" aria-busy="true" aria-label="Memuat beranda">
                    <div
                        style="display: flex; gap: 12px; align-items: center; border-bottom: 1px solid #d1d5db; padding-bottom: 10px;">
                        <div class="isf-skel-el" style="width: 64px; height: 14px;"></div>
                        <div class="isf-skel-el" style="width: 40%; height: 14px;"></div>
                    </div>
                    <div class="isf-skel-rule" style="margin-top: 16px;">
                        <div class="isf-skel-el" style="height: 320px; border-radius: 0;"></div>
                        <div
                            style="display: flex; justify-content: space-between; border-top: 1px solid #d1d5db; padding: 12px;">
                            <div class="isf-skel-el" style="width: 120px; height: 14px;"></div>
                            <div class="isf-skel-el" style="width: 72px; height: 24px;"></div>
                        </div>
                    </div>
                    <div class="isf-skel-grid isf-skel-rule" style="margin-top: 24px;">
                        <section style="padding: 20px; border-right: 1px solid #d1d5db;">
                            <div class="isf-skel-el" style="width: 80px; height: 14px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-el" style="width: 85%; height: 32px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-el" style="height: 16px; margin-bottom: 8px;"></div>
                            <div class="isf-skel-el" style="width: 90%; height: 160px;"></div>
                        </section>
                        <section style="padding: 20px; border-right: 1px solid #d1d5db;">
                            <div class="isf-skel-el" style="width: 80px; height: 14px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-el" style="width: 85%; height: 38px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-lines">
                                <div class="isf-skel-el" style="height: 16px;"></div>
                                <div class="isf-skel-el" style="width: 90%; height: 16px;"></div>
                            </div>
                            <div class="isf-skel-el" style="height: 180px; margin-top: 20px;"></div>
                        </section>
                        <aside style="padding: 20px;">
                            @for ($i = 0; $i < 3; $i++)
                                <div style="border-bottom: 1px solid #d1d5db; padding: 0 0 16px; margin-bottom: 16px;">
                                    <div class="isf-skel-el" style="height: 120px; margin-bottom: 12px;"></div>
                                    <div class="isf-skel-el" style="height: 16px; margin-bottom: 8px;"></div>
                                    <div class="isf-skel-el" style="width: 45%; height: 12px;"></div>
                                </div>
                            @endfor
                        </aside>
                    </div>
                    <section class="isf-skel-rule" style="margin-top: 24px;">
                        <div style="border-bottom: 1px solid #d1d5db; padding: 14px;">
                            <div class="isf-skel-el" style="width: 150px; height: 14px;"></div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            @for ($i = 0; $i < 4; $i++)
                                <div style="padding: 16px; border-right: 1px solid #d1d5db;">
                                    <div class="isf-skel-el" style="height: 150px; margin-bottom: 12px;"></div>
                                    <div class="isf-skel-el" style="height: 18px;"></div>
                                </div>
                            @endfor
                        </div>
                    </section>
                    <section class="isf-skel-rule" style="margin-top: 24px;">
                        <div style="border-bottom: 1px solid #d1d5db; padding: 14px;">
                            <div class="isf-skel-el" style="width: 100px; height: 14px;"></div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                            @for ($i = 0; $i < 2; $i++)
                                <div style="padding: 20px;">
                                    <div class="isf-skel-el" style="width: 72px; height: 12px; margin-bottom: 12px;">
                                    </div>
                                    <div class="isf-skel-el" style="height: 26px; margin-bottom: 12px;"></div>
                                    <div class="isf-skel-el" style="height: 16px;"></div>
                                </div>
                            @endfor
                        </div>
                    </section>
                </main>
            @endif
        </div>
    </div>
</body>

</html>
