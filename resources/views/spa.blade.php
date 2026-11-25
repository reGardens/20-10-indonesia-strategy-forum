<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Indonesia Strategy Forum</title>
    <link rel="icon" type="image/png" href="{{ asset('images/isf/ISF-Logo-2.png') }}?v=2">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-180x180.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0B1220">
    <meta name="application-name" content="Indonesia Strategy Forum">
    <meta name="apple-mobile-web-app-title" content="ISF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    @php
        // Status login untuk header publik (dibaca sinkron oleh useIsfSession) → tidak tampil "Login" sekejap.
        // Hanya nama + status Nomor WhatsApp milik user sendiri (Issue #42).
        $isfSessionUser = auth()->user();
        $isfSessionPayload = $isfSessionUser
            ? ['authenticated' => true, 'name' => (string) $isfSessionUser->name]
                + \App\Http\Controllers\Api\Isf\SessionController::whatsapp($isfSessionUser)
            : ['authenticated' => false, 'name' => null];
    @endphp
    <script>
        {{-- Directive json Blade meng-escape <, >, &, ' dan " → aman di dalam <script>. --}}
        window.__ISF_SESSION__ = @json($isfSessionPayload);
        (function() {
            try {
                var font = localStorage.getItem('isf_header_font') || 'montserrat';
                document.documentElement.setAttribute('data-isf-font', font);
                // Font header Roboto (pilihan pengguna) ikut di-preload agar tidak tampil sebagai font sistem dulu.
                if (font === 'roboto') {
                    var preload = document.createElement('link');
                    preload.rel = 'preload';
                    preload.as = 'font';
                    preload.type = 'font/woff2';
                    preload.crossOrigin = 'anonymous';
                    preload.href = "{{ asset('fonts/roboto-latin.woff2') }}";
                    document.head.appendChild(preload);
                }
                document.documentElement.setAttribute('data-isf-layout', 'row-3');
            } catch(e) {}
        })();
    </script>
    <!-- Preload font lokal; @font-face & semua style landing ada di resources/css/isf.css -->
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('fonts/inter-latin.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('fonts/newsreader-latin.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('fonts/montserrat-latin.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('fonts/space-grotesk-latin.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('fonts/anton-latin.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('fonts/pinyon-script-400.woff2') }}">
    @if (\Illuminate\Support\Facades\Vite::isRunningHot())
        {{-- Hanya saat npm run dev: CSS dari Vite baru masuk lewat JavaScript, jadi bagian font, skeleton,
             dan header loading dari resources/css/isf.css disisipkan di sini agar layout tidak bergeser saat refresh.
             Staging/production memakai file CSS hasil build di <head> (sudah termuat sebelum halaman tampil). --}}
        @php
            $isfCss = (string) @file_get_contents(resource_path('css/isf.css'));
            $isfPart3 = strpos($isfCss, '/* ── 3. ');
            $isfPart5 = strpos($isfCss, '/* ── 5. ');
        @endphp
        @if ($isfPart3 !== false && $isfPart5 !== false)
            <style>{!! substr($isfCss, 0, $isfPart3) . substr($isfCss, $isfPart5) !!}</style>
        @endif
    @endif
    @vite(['resources/js/spa.js'])
</head>

<body class="bg-white antialiased">
    <div id="app">
        <div class="isf-initial-shell isf-theme">
            @if (request()->is('anggota*'))
                @include('partials.isf-member-masthead')
                <div class="isf-skel-member-layout isf-skel-member-layout--full isf-skel-pulse" aria-busy="true"
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
                                <div class="isf-skel-subgrid isf-skel-member-toolbar"
                                    style="border-top: 1px solid #d1d5db; border-bottom: 1px solid #d1d5db; padding: 16px 0;">
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
                                        <div class="isf-skel-subgrid">
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
            @elseif(request()->is('politik') || request()->is('politik/*'))
                @include('partials.isf-masthead')
                {{-- Hero Header Rubrik Politik (Navy Identik dengan Hukum) --}}
                <div style="background-color: #0B1220; border-bottom: 1px solid #262626; padding: 2rem 1rem 2rem; color: #fff;">
                    <div style="max-width: 1440px; margin: 0 auto; padding: 0 1rem;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 1rem;">
                            <div class="isf-skel-el" style="width: 60px; height: 12px; background: rgba(255,255,255,0.15);"></div>
                            <span style="color: rgba(255,255,255,0.3); font-size: 11px;">/</span>
                            <div class="isf-skel-el" style="width: 48px; height: 12px; background: rgba(255,255,255,0.25);"></div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 1.5rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #E10600; border-radius: 2px;"></span>
                                    <span style="font-family: monospace; font-size: 11px; font-weight: bold; color: #E10600; text-transform: uppercase; letter-spacing: 0.1em;">Rubrik Dinamika Politik & Parlemen</span>
                                </div>
                                <div class="isf-skel-el" style="width: 220px; height: 48px; background: rgba(255,255,255,0.2); margin-bottom: 8px;"></div>
                                <div class="isf-skel-el" style="width: min(600px, 90%); height: 16px; background: rgba(255,255,255,0.15);"></div>
                            </div>
                            <div style="display: flex; gap: 8px; overflow-x: auto; padding-top: 4px;">
                                <div class="isf-skel-el" style="width: 110px; height: 32px; border-radius: 9999px; background: #E10600; flex-shrink: 0;"></div>
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="isf-skel-el" style="width: 120px; height: 32px; border-radius: 9999px; background: rgba(255,255,255,0.1); flex-shrink: 0;"></div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>

                <main class="isf-skel-article isf-skel-article--full isf-skel-pulse" aria-busy="true" aria-label="Memuat rubrik politik">
                    <div class="isf-skel-stack">
                        {{-- Section 1: Headline --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 160px; height: 24px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div class="isf-skel-el" style="height: 320px;"></div>
                                <div class="isf-skel-el" style="width: 70%; height: 28px;"></div>
                                <div class="isf-skel-el" style="height: 16px;"></div>
                            </div>
                        </section>
                        {{-- Section 2: Peta Kursi Parlemen Navy --}}
                        <section class="isf-skel-frame isf-skel-frame--navy">
                            <div class="isf-skel-sechead" style="display: flex; align-items: center; gap: 8px;">
                                <span style="display: inline-block; width: 10px; height: 10px; background-color: #E10600; border-radius: 2px;"></span>
                                <div class="isf-skel-el" style="width: 220px; height: 24px; background: rgba(255,255,255,0.2);"></div>
                            </div>
                            <div class="isf-skel-framebody" style="padding: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; align-items: center;">
                                <div style="display: flex; justify-content: center;">
                                    <div class="isf-skel-el" style="width: 220px; height: 220px; border-radius: 9999px; background: rgba(255,255,255,0.1);"></div>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    @for ($i = 0; $i < 6; $i++)
                                        <div class="isf-skel-el" style="height: 36px; border-radius: 6px; background: rgba(255,255,255,0.08);"></div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                        {{-- Section 3: Profil Tokoh Politik Cream --}}
                        <section class="isf-skel-frame isf-skel-frame--cream">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 180px; height: 24px;"></div></div>
                            <div class="isf-skel-framebody" style="padding: 20px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div style="background: rgba(0,0,0,0.03); padding: 12px; border-radius: 6px; border: 1px solid rgba(0,0,0,0.06);">
                                            <div class="isf-skel-el" style="height: 160px; margin-bottom: 12px;"></div>
                                            <div class="isf-skel-el" style="width: 70%; height: 18px; margin-bottom: 6px;"></div>
                                            <div class="isf-skel-el" style="width: 50%; height: 14px;"></div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                        {{-- Section 4: Artikel Terbaru Grid 2 Kolom --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 150px; height: 24px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div style="display: flex; gap: 12px; padding: 12px; border-bottom: 1px solid #e5e7eb;">
                                            <div class="isf-skel-el" style="width: 120px; height: 80px; flex-shrink: 0;"></div>
                                            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                                <div class="isf-skel-el" style="width: 85%; height: 18px;"></div>
                                                <div class="isf-skel-el" style="width: 100%; height: 14px;"></div>
                                                <div class="isf-skel-el" style="width: 40%; height: 12px;"></div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                    </div>
                </main>
            @elseif(request()->is('hukum') || request()->is('hukum/*'))
                @include('partials.isf-masthead')
                {{-- Hero Header Rubrik Hukum (Navy Identik dengan Politik) --}}
                <div style="background-color: #0B1220; border-bottom: 1px solid #262626; padding: 2rem 1rem 2rem; color: #fff;">
                    <div style="max-width: 1440px; margin: 0 auto; padding: 0 1rem;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 1rem;">
                            <div class="isf-skel-el" style="width: 60px; height: 12px; background: rgba(255,255,255,0.15);"></div>
                            <span style="color: rgba(255,255,255,0.3); font-size: 11px;">/</span>
                            <div class="isf-skel-el" style="width: 48px; height: 12px; background: rgba(255,255,255,0.25);"></div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 1.5rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #E10600; border-radius: 2px;"></span>
                                    <span style="font-family: monospace; font-size: 11px; font-weight: bold; color: #E10600; text-transform: uppercase; letter-spacing: 0.1em;">Rubrik Kajian Yuridis & Regulasi</span>
                                </div>
                                <div class="isf-skel-el" style="width: 200px; height: 48px; background: rgba(255,255,255,0.2); margin-bottom: 8px;"></div>
                                <div class="isf-skel-el" style="width: min(600px, 90%); height: 16px; background: rgba(255,255,255,0.15);"></div>
                            </div>
                            <div style="display: flex; gap: 8px; overflow-x: auto; padding-top: 4px;">
                                <div class="isf-skel-el" style="width: 110px; height: 32px; border-radius: 9999px; background: #E10600; flex-shrink: 0;"></div>
                                @for ($i = 0; $i < 3; $i++)
                                    <div class="isf-skel-el" style="width: 130px; height: 32px; border-radius: 9999px; background: rgba(255,255,255,0.1); flex-shrink: 0;"></div>
                                @endfor
                                <div class="isf-skel-el" style="width: 110px; height: 32px; border-radius: 9999px; background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); flex-shrink: 0;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <main class="isf-skel-article isf-skel-article--full isf-skel-pulse" aria-busy="true" aria-label="Memuat rubrik hukum">
                    <div class="isf-skel-stack">
                        {{-- Section 1: Headline --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 160px; height: 24px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div class="isf-skel-el" style="height: 320px;"></div>
                                <div class="isf-skel-el" style="width: 70%; height: 28px;"></div>
                                <div class="isf-skel-el" style="height: 16px;"></div>
                            </div>
                        </section>
                        {{-- Section 2: Timeline Kasus Navy --}}
                        <section class="isf-skel-frame isf-skel-frame--navy">
                            <div class="isf-skel-sechead" style="display: flex; align-items: center; gap: 8px;">
                                <span style="display: inline-block; width: 10px; height: 10px; background-color: #E10600; border-radius: 2px;"></span>
                                <div class="isf-skel-el" style="width: 240px; height: 24px; background: rgba(255,255,255,0.2);"></div>
                            </div>
                            <div class="isf-skel-framebody" style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                                <div class="isf-skel-el" style="height: 48px; border-radius: 6px; background: rgba(255,255,255,0.08);"></div>
                                <div class="isf-skel-el" style="height: 80px; border-radius: 6px; background: rgba(255,255,255,0.05);"></div>
                            </div>
                        </section>
                        {{-- Section 3: Opini Pakar Hukum Cream --}}
                        <section class="isf-skel-frame isf-skel-frame--cream">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 180px; height: 24px;"></div></div>
                            <div class="isf-skel-framebody" style="padding: 20px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div style="background: rgba(0,0,0,0.03); padding: 12px; border-radius: 6px; border: 1px solid rgba(0,0,0,0.06);">
                                            <div class="isf-skel-el" style="width: 56px; height: 56px; border-radius: 9999px; margin-bottom: 12px;"></div>
                                            <div class="isf-skel-el" style="width: 70%; height: 18px; margin-bottom: 6px;"></div>
                                            <div class="isf-skel-el" style="width: 50%; height: 14px;"></div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                        {{-- Section 5: Artikel Terbaru Grid 2 Kolom --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 150px; height: 24px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div style="display: flex; gap: 12px; padding: 12px; border-bottom: 1px solid #e5e7eb;">
                                            <div class="isf-skel-el" style="width: 120px; height: 80px; flex-shrink: 0;"></div>
                                            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                                <div class="isf-skel-el" style="width: 85%; height: 18px;"></div>
                                                <div class="isf-skel-el" style="width: 100%; height: 14px;"></div>
                                                <div class="isf-skel-el" style="width: 40%; height: 12px;"></div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                    </div>
                </main>
            @elseif(request()->is('kebijakan-publik') || request()->is('kebijakan-publik/*'))
                @include('partials.isf-masthead')
                <main class="isf-skel-article isf-skel-article--full isf-skel-pulse" aria-busy="true" aria-label="Memuat rubrik kebijakan publik">
                    <div class="isf-skel-el" style="width: 150px; height: 12px; margin-bottom: 16px;"></div>
                    <div class="isf-skel-el" style="width: 280px; height: 42px; margin-bottom: 12px;"></div>
                    <div class="isf-skel-el" style="width: 55%; height: 18px; margin-bottom: 24px;"></div>
                    <div style="display: flex; gap: 8px; margin-bottom: 40px; flex-wrap: wrap;">
                        @for ($i = 0; $i < 7; $i++)
                            <div class="isf-skel-el" style="width: 130px; height: 36px; border-radius: 9999px;"></div>
                        @endfor
                    </div>
                    <div class="isf-skel-stack">
                        {{-- Section 1: Headline --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 200px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div class="isf-skel-el" style="height: 300px;"></div>
                                <div class="isf-skel-el" style="width: 75%; height: 28px;"></div>
                                <div class="isf-skel-el" style="height: 16px;"></div>
                            </div>
                        </section>
                        {{-- Section 2: Policy Brief Cream --}}
                        <section class="isf-skel-frame isf-skel-frame--cream">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 220px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody" style="padding: 24px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="isf-skel-el" style="height: 200px;"></div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                        {{-- Section 3: Monitoring Kebijakan Navy --}}
                        <section class="isf-skel-frame isf-skel-frame--navy">
                            <div class="isf-skel-sechead" style="display: flex; align-items: center; gap: 8px;">
                                <span style="display: inline-block; width: 10px; height: 10px; background-color: #E10600; border-radius: 2px;"></span>
                                <div class="isf-skel-el" style="width: 240px; height: 24px; background: rgba(255,255,255,0.2);"></div>
                            </div>
                            <div class="isf-skel-framebody" style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                                <div class="isf-skel-el" style="height: 48px; border-radius: 6px; background: rgba(255,255,255,0.08);"></div>
                                <div class="isf-skel-el" style="height: 80px; border-radius: 6px; background: rgba(255,255,255,0.05);"></div>
                            </div>
                        </section>
                        {{-- Section 5: Artikel Terbaru Grid 2 Kolom --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 150px; height: 24px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div style="display: flex; gap: 12px; padding: 12px; border-bottom: 1px solid #e5e7eb;">
                                            <div class="isf-skel-el" style="width: 120px; height: 80px; flex-shrink: 0;"></div>
                                            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                                <div class="isf-skel-el" style="width: 85%; height: 18px;"></div>
                                                <div class="isf-skel-el" style="width: 100%; height: 14px;"></div>
                                                <div class="isf-skel-el" style="width: 40%; height: 12px;"></div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                    </div>
                </main>
            @elseif(request()->is('opini-publik') || request()->is('opini-publik/*'))
                @include('partials.isf-masthead')
                <main class="isf-skel-article isf-skel-article--full isf-skel-pulse" aria-busy="true" aria-label="Memuat rubrik opini publik">
                    <div class="isf-skel-el" style="width: 150px; height: 12px; margin-bottom: 16px;"></div>
                    <div class="isf-skel-el" style="width: 280px; height: 42px; margin-bottom: 12px;"></div>
                    <div class="isf-skel-el" style="width: 55%; height: 18px; margin-bottom: 24px;"></div>
                    <div style="display: flex; gap: 8px; margin-bottom: 40px; flex-wrap: wrap;">
                        @for ($i = 0; $i < 5; $i++)
                            <div class="isf-skel-el" style="width: 130px; height: 36px; border-radius: 9999px;"></div>
                        @endfor
                    </div>
                    <div class="isf-skel-stack">
                        {{-- Section 1: Headline --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 200px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div class="isf-skel-el" style="height: 300px;"></div>
                                <div class="isf-skel-el" style="width: 75%; height: 28px;"></div>
                                <div class="isf-skel-el" style="height: 16px;"></div>
                            </div>
                        </section>
                        {{-- Section 2: Reader Poll Navy --}}
                        <section class="isf-skel-frame isf-skel-frame--navy">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 220px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody" style="padding: 24px;">
                                <div class="isf-skel-el" style="height: 180px;"></div>
                            </div>
                        </section>
                        {{-- Section 3: Kutipan Publik Cream --}}
                        <section class="isf-skel-frame isf-skel-frame--cream">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 200px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody" style="padding: 24px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="isf-skel-el" style="height: 160px;"></div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                        {{-- Section 4: Surat Pembaca White --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 200px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines" style="padding: 24px;">
                                <div class="isf-skel-el" style="height: 100px; margin-bottom: 12px;"></div>
                                <div class="isf-skel-el" style="height: 100px;"></div>
                            </div>
                        </section>
                        {{-- Section 5: Artikel Terbaru Grid 2 Kolom --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 150px; height: 24px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div style="display: flex; gap: 12px; padding: 12px; border-bottom: 1px solid #e5e7eb;">
                                            <div class="isf-skel-el" style="width: 120px; height: 80px; flex-shrink: 0;"></div>
                                            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                                <div class="isf-skel-el" style="width: 85%; height: 18px;"></div>
                                                <div class="isf-skel-el" style="width: 100%; height: 14px;"></div>
                                                <div class="isf-skel-el" style="width: 40%; height: 12px;"></div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                    </div>
                </main>
            @elseif(request()->is('survei') || request()->is('survei/*'))
                @include('partials.isf-masthead')
                <main class="isf-skel-article isf-skel-article--full isf-skel-pulse" aria-busy="true" aria-label="Memuat rubrik survei">
                    <div class="isf-skel-el" style="width: 140px; height: 12px; margin-bottom: 16px;"></div>
                    <div class="isf-skel-el" style="width: 260px; height: 42px; margin-bottom: 12px;"></div>
                    <div class="isf-skel-el" style="width: 50%; height: 18px; margin-bottom: 24px;"></div>
                    <div style="display: flex; gap: 8px; margin-bottom: 40px; flex-wrap: wrap;">
                        @for ($i = 0; $i < 5; $i++)
                            <div class="isf-skel-el" style="width: 120px; height: 36px; border-radius: 9999px;"></div>
                        @endfor
                    </div>
                    <div class="isf-skel-stack">
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 220px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div class="isf-skel-el" style="height: 240px;"></div>
                                <div class="isf-skel-el" style="width: 75%; height: 28px;"></div>
                                <div class="isf-skel-el" style="height: 16px;"></div>
                            </div>
                        </section>
                        <section class="isf-skel-frame isf-skel-frame--cream">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 200px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody" style="padding: 24px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="isf-skel-el" style="height: 220px;"></div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                    </div>
                </main>
            @elseif(in_array(request()->path(), array_map(fn ($k) => 'artikel/' . $k, \App\Support\IsfRubrik::keys()), true))
                @include('partials.isf-masthead')
                <main class="isf-skel-article isf-skel-article--full isf-skel-pulse" aria-busy="true" aria-label="Memuat rubrik">
                    <div class="isf-skel-el" style="width: 100px; height: 12px; margin-bottom: 16px;"></div>
                    <div class="isf-skel-el" style="width: 40%; height: 42px; margin-bottom: 12px;"></div>
                    <div class="isf-skel-el" style="width: 60%; height: 18px; margin-bottom: 40px;"></div>
                    {{-- = RubrikView: Sorotan Utama (accent) + Artikel Terbaru (krem), jarak 48px --}}
                    <div class="isf-skel-stack">
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 160px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                <div class="isf-skel-el" style="height: 320px;"></div>
                                <div class="isf-skel-el" style="width: 70%; height: 30px;"></div>
                                <div class="isf-skel-el" style="height: 16px;"></div>
                            </div>
                        </section>
                        <section class="isf-skel-frame isf-skel-frame--cream">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 180px; height: 26px;"></div></div>
                            <div class="isf-skel-framebody isf-skel-lines">
                                @for ($i = 0; $i < 3; $i++)
                                    <div class="isf-skel-el" style="height: 72px;"></div>
                                @endfor
                            </div>
                        </section>
                    </div>
                </main>
            @elseif(request()->is('artikel/*') || request()->is('infografik/*'))
                @include('partials.isf-masthead')
                <main class="isf-skel-article isf-skel-article--full isf-skel-pulse" aria-busy="true" aria-label="{{ request()->is('infografik/*') ? 'Memuat infografik' : 'Memuat artikel' }}">
                    <div class="isf-skel-el" style="width: 160px; height: 12px; margin-bottom: 32px;"></div>
                    {{-- = ArticleDetailView / InfografikDetailView: section berbingkai (accent) + sidebar Terbaru, jarak 48px --}}
                    <div class="isf-skel-article-grid isf-skel-tokoh">
                        <article class="isf-skel-stack">
                            <section class="isf-skel-frame isf-skel-frame--accent">
                                <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 130px; height: 26px;"></div></div>
                                <div class="isf-skel-framebody isf-skel-lines">
                                    <div class="isf-skel-el" style="height: 36px;"></div>
                                    <div class="isf-skel-el" style="width: 75%; height: 36px;"></div>
                                    <div class="isf-skel-el" style="height: 18px;"></div>
                                    <div class="isf-skel-el" style="width: 85%; height: 18px;"></div>
                                    <div class="isf-skel-el" style="height: 44px;"></div>
                                </div>
                                <div class="isf-skel-el" style="height: 320px; border-radius: 0;"></div>
                                <div class="isf-skel-framebody isf-skel-lines">
                                    @for ($i = 1; $i <= 6; $i++)
                                        <div class="isf-skel-el" style="width: {{ $i % 3 === 0 ? '80%' : '100%' }}; height: 16px;"></div>
                                    @endfor
                                </div>
                            </section>
                        </article>
                        <aside class="isf-skel-stack">
                            <section class="isf-skel-frame isf-skel-frame--accent">
                                <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 120px; height: 26px;"></div></div>
                                <div class="isf-skel-framebody isf-skel-lines">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div class="isf-skel-el" style="height: 64px;"></div>
                                    @endfor
                                </div>
                            </section>
                        </aside>
                    </div>
                </main>
            @elseif(request()->is('tokoh/*') || request()->is('sosok') || request()->is('sosok/*'))
                @include('partials.isf-masthead')
                <main class="isf-skel-article isf-skel-article--full isf-skel-pulse" aria-busy="true" aria-label="Memuat profil tokoh">
                    <div class="isf-skel-el" style="width: 120px; height: 12px; margin-bottom: 16px;"></div>
                    <div class="isf-skel-el" style="width: 55%; height: 42px; margin-bottom: 32px;"></div>
                    {{-- = TokohProfileView: section berbingkai (accent/krem) + judul section besar, jarak 48px --}}
                    <div class="isf-skel-article-grid isf-skel-tokoh">
                        <article class="isf-skel-stack">
                            <section class="isf-skel-frame isf-skel-frame--accent">
                                <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 160px; height: 26px;"></div></div>
                                <div class="isf-skel-subgrid isf-skel-identity isf-skel-framebody">
                                    <div class="isf-skel-el" style="height: 210px;"></div>
                                    <div class="isf-skel-lines">
                                        <div class="isf-skel-el" style="width: 75%; height: 32px;"></div>
                                        <div class="isf-skel-el" style="height: 16px;"></div>
                                        {{-- 3 statistik Indeks Tokoh: Karya, Tingkat Kesukaan, Tidak Suka --}}
                                        <div class="isf-skel-subgrid isf-skel-stat3">
                                            @for ($i = 0; $i < 3; $i++)
                                                <div class="isf-skel-el" style="height: 64px;"></div>
                                            @endfor
                                        </div>
                                        <div class="isf-skel-el" style="height: 56px;"></div>
                                        <div class="isf-skel-el" style="width: 128px; height: 36px;"></div>
                                    </div>
                                </div>
                            </section>
                            <section class="isf-skel-frame isf-skel-frame--cream">
                                <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 270px; height: 26px;"></div></div>
                                <div class="isf-skel-framebody isf-skel-lines">
                                    <div class="isf-skel-el" style="height: 84px;"></div>
                                    <div class="isf-skel-el" style="width: min(320px, 100%); height: 32px;"></div>
                                    <div class="isf-skel-rule">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="isf-skel-el"
                                                style="height: 48px; border-radius: 0; border-bottom: 1px solid #e5e7eb;">
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            </section>
                        </article>
                        <aside class="isf-skel-stack">
                            <section class="isf-skel-frame isf-skel-frame--accent">
                                <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 140px; height: 26px;"></div></div>
                                <div class="isf-skel-framebody isf-skel-lines">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="isf-skel-el" style="height: 20px;"></div>
                                    @endfor
                                </div>
                            </section>
                            <section class="isf-skel-frame isf-skel-frame--cream">
                                <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 140px; height: 26px;"></div></div>
                                <div class="isf-skel-framebody">
                                    <div class="isf-skel-el" style="height: 260px;"></div>
                                </div>
                            </section>
                        </aside>
                    </div>
                </main>
            @elseif(request()->is('media-kit') || request()->is('advertise'))
                @include('partials.isf-masthead')
                {{-- Hero Header Media Kit (Navy) --}}
                <div style="background-color: #0B1220; border-bottom: 1px solid #262626; padding: 2.5rem 1rem 3rem; color: #fff;">
                    <div style="max-width: 1440px; margin: 0 auto; padding: 0 1rem;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 1.5rem;">
                            <div class="isf-skel-el" style="width: 60px; height: 12px; background: rgba(255,255,255,0.15);"></div>
                            <span style="color: rgba(255,255,255,0.3); font-size: 11px;">/</span>
                            <div class="isf-skel-el" style="width: 110px; height: 12px; background: rgba(255,255,255,0.25);"></div>
                        </div>
                        <div style="max-width: 800px; display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="display: inline-block; width: 10px; height: 10px; background-color: #E10600; border-radius: 2px;"></span>
                                <span style="font-family: monospace; font-size: 11px; font-weight: bold; color: #E10600; text-transform: uppercase; letter-spacing: 0.1em;">PANDUAN KEMITRAAN & MEDIA KIT ISF</span>
                            </div>
                            <div class="isf-skel-el" style="width: min(720px, 95%); height: 48px; background: rgba(255,255,255,0.2);"></div>
                            <div class="isf-skel-el" style="width: min(580px, 85%); height: 20px; background: rgba(255,255,255,0.15);"></div>
                            <div style="display: flex; gap: 12px; margin-top: 1rem;">
                                <div class="isf-skel-el" style="width: 190px; height: 44px; background: #E10600;"></div>
                                <div class="isf-skel-el" style="width: 150px; height: 44px; background: rgba(255,255,255,0.1);"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Content Skeleton --}}
                <main class="isf-skel-article isf-skel-article--full isf-skel-pulse" aria-busy="true" aria-label="Memuat panduan kemitraan">
                    <div class="isf-skel-stack">
                        {{-- Section 1: Executive Metrics --}}
                        <section class="isf-skel-frame isf-skel-frame--cream" style="padding: 24px;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                @for ($i = 0; $i < 4; $i++)
                                    <div style="border-left: 3px solid #E10600; padding-left: 16px;">
                                        <div class="isf-skel-el" style="width: 90px; height: 36px; margin-bottom: 8px;"></div>
                                        <div class="isf-skel-el" style="width: 120px; height: 16px; margin-bottom: 4px;"></div>
                                        <div class="isf-skel-el" style="width: 80%; height: 12px;"></div>
                                    </div>
                                @endfor
                            </div>
                        </section>
                        {{-- Section 2: Channels Grid --}}
                        <section class="isf-skel-frame isf-skel-frame--accent">
                            <div class="isf-skel-sechead"><div class="isf-skel-el" style="width: 220px; height: 24px;"></div></div>
                            <div class="isf-skel-framebody" style="padding: 24px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div style="border: 1px solid #e5e7eb; border-top: 3px solid #E10600; padding: 20px;">
                                            <div class="isf-skel-el" style="width: 80px; height: 14px; margin-bottom: 12px;"></div>
                                            <div class="isf-skel-el" style="width: 180px; height: 24px; margin-bottom: 12px;"></div>
                                            <div class="isf-skel-el" style="height: 14px; margin-bottom: 6px;"></div>
                                            <div class="isf-skel-el" style="width: 80%; height: 14px;"></div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </section>
                    </div>
                </main>
            @else
                @include('partials.isf-masthead')
                <main class="isf-skel-container isf-skel-container--full isf-skel-pulse" aria-busy="true" aria-label="Memuat beranda">
                    <div
                        style="display: flex; gap: 12px; align-items: center; border-bottom: 1px solid #d1d5db; padding-bottom: 10px;">
                        <div class="isf-skel-el" style="width: 64px; height: 14px;"></div>
                        <div class="isf-skel-el" style="width: 40%; height: 14px;"></div>
                    </div>
                    <div class="isf-skel-rule" style="margin-top: 16px;">
                        <div class="isf-skel-el" style="height: 320px; border-radius: 0;"></div>
                        <div
                            style="display: flex; align-items: center; gap: 12px; border-top: 1px solid #d1d5db; padding: 8px 12px; min-height: 42px;">
                            <div class="isf-skel-el" style="width: 72px; height: 20px;"></div>
                            <div class="isf-skel-el" style="width: 50%; height: 14px;"></div>
                        </div>
                    </div>
                    <div class="isf-skel-grid isf-skel-rule" style="margin-top: 24px;">
                        <section class="isf-skel-beranda-insight" style="padding: 20px; border-right: 1px solid #d1d5db;">
                            <div class="isf-skel-el" style="width: 80px; height: 14px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-el" style="width: 85%; height: 32px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-el" style="height: 16px; margin-bottom: 8px;"></div>
                            <div class="isf-skel-el" style="width: 90%; height: 160px;"></div>
                        </section>
                        <section class="isf-skel-beranda-hero" style="padding: 20px; border-right: 1px solid #d1d5db;">
                            <div class="isf-skel-el" style="width: 80px; height: 14px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-el" style="width: 85%; height: 38px; margin-bottom: 16px;"></div>
                            <div class="isf-skel-lines">
                                <div class="isf-skel-el" style="height: 16px;"></div>
                                <div class="isf-skel-el" style="width: 90%; height: 16px;"></div>
                            </div>
                            <div class="isf-skel-el" style="height: 180px; margin-top: 20px;"></div>
                        </section>
                        <aside class="isf-skel-beranda-media" style="padding: 20px;">
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
