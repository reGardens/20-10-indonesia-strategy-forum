<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Indonesia Strategy Forum</title>
    <link rel="icon" type="image/png" href="{{ asset('images/isf/ISF-Logo-2.png') }}?v=2">
    <link rel="apple-touch-icon" href="{{ asset('images/isf/ISF-Logo-2.png') }}?v=2">
    <meta name="theme-color" content="#0B1220">
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
                document.documentElement.setAttribute('data-isf-font', 'montserrat');
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
    <div id="app"></div>
</body>

</html>
