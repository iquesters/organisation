<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ $organisation->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body class="bg-body-tertiary">
<main class="container py-4" style="max-width: 860px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h1 class="fs-4 mb-1">{{ $title }}</h1>
            <div class="text-muted small mb-3">
                {{ $organisation->name }}@if ($version !== 'platform') · {{ __('Version') }} {{ $version }}@endif
            </div>
            {{-- Plain text written by the organisation's admins: escaped, line breaks kept. --}}
            <div style="white-space: pre-line;">{{ $text }}</div>
        </div>
    </div>
</main>
</body>
</html>
