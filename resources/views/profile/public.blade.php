@php
    $hasLogo = ! empty($values[\Iquesters\Organisation\Support\OrganisationProfile::LOGO]);
    $grouped = collect($fields)->groupBy('group');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $organisation->name }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($values['tagline'] ?? ($values['about'] ?? $organisation->name), 160) }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body class="bg-body-tertiary">
<main class="container py-4" style="max-width: 960px;">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            @if ($hasLogo)
                <img src="{{ route('organisations.logo', $organisation->uid) }}" alt="{{ $organisation->name }} logo" class="rounded border bg-white" style="width: 88px; height: 88px; object-fit: contain;">
            @endif
            <div>
                <h1 class="fs-4 mb-1">{{ $organisation->name }}</h1>
                @if (! empty($values['tagline']))<div class="text-muted">{{ $values['tagline'] }}</div>@endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8 d-grid gap-3 align-content-start">
            @foreach ($groups as $group => $title)
                @php($rows = collect($grouped[$group] ?? [])->filter(fn ($f) => ! empty($values[$f['id']])))
                @if ($rows->isNotEmpty())
                    <section class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h2 class="fs-6 mb-3">{{ $title }}</h2>
                            <dl class="row small mb-0">
                                @foreach ($rows as $field)
                                    @php($value = $values[$field['id']])
                                    <dt class="col-sm-4 text-muted fw-normal">{{ $field['label'] }}</dt>
                                    <dd class="col-sm-8" style="white-space: pre-line;">@if ($field['type'] === 'url')<a href="{{ $value }}" target="_blank" rel="noopener nofollow">{{ $value }}</a>@elseif ($field['type'] === 'email')<a href="mailto:{{ $value }}">{{ $value }}</a>@else{{ $value }}@endif</dd>
                                @endforeach
                            </dl>
                        </div>
                    </section>
                @endif
            @endforeach
        </div>

        @if ($officeBearers->isNotEmpty())
            <div class="col-12 col-lg-4">
                <section class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="fs-6 mb-3">Office bearers</h2>
                        @foreach ($officeBearers as $bearer)
                            <div class="py-2 border-bottom small">
                                <div class="text-muted">{{ $bearer->title }}</div>
                                <div class="fw-semibold">{{ $bearer->name }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        @endif
    </div>
    @if (! empty($policyLinks))
        <footer class="small text-muted text-center mt-3">
            @foreach ($policyLinks as $link)
                <a href="{{ $link['url'] }}" class="text-muted">{{ $link['label'] }}</a>@if (! $loop->last) · @endif
            @endforeach
        </footer>
    @endif
</main>
</body>
</html>
