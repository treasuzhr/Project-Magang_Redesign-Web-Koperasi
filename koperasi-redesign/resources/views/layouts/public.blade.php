<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#7fad39">
    <meta name="description" content="@yield('description', 'Temukan produk dan informasi Primkop Pullahta Hankam, Pusdatin Kemhan.')">
    <title>@yield('title', 'Primkop Pullahta Hankam | Koperasi Pusdatin Kemhan')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-navbar />
    @yield('content')
    <x-footer />
</body>
</html>
