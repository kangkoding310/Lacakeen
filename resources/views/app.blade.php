<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Lacakeen') }}</title>

    <!-- SEO -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Lacakeen – Modern Task Management">
    <meta property="og:description" content="A modern task management application for teams and individuals">
    <meta property="og:site_name" content="Lacakeen">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Lacakeen – Modern Task Management">
    <meta name="twitter:description" content="A modern task management application for teams and individuals.">

    <meta name="author" content="Fadhlan Minallah">
    <meta name="creator" content="Fadhlan Minallah">
    <meta name="publisher" content="Fadhlan Minallah">
    <link rel="author" href="https://github.com/FadhlanMinallah">
    <meta name="description" content="Lacakeen - Task Management Application">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>