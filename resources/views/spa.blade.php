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
        href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Roboto:wght@400;500;700&family=Source+Serif+4:wght@400;600&display=swap"
        rel="stylesheet">
    @vite(['resources/js/spa.js'])
</head>

<body class="bg-gray-50 antialiased">
    <div id="app"></div>
</body>

</html>
