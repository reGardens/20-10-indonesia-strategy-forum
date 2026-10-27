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

    @vite(['resources/js/spa.js'])
</head>

<body class="bg-white antialiased">

    <div id="app"></div>
</body>

</html>
