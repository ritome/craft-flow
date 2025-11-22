<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>予約システム</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    {{ $slot }}

    {{-- ★★★ この一行を追加 ★★★ --}}
    <x-email-preview-modal />
</body>

</html>
