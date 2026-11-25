@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : \Iquesters\Foundation\Support\ConfProvider::from(\Iquesters\Foundation\Enums\Module::ORGANISATION)->organisation_layout;
    $hasLogo = ! empty($values[\Iquesters\Organisation\Support\OrganisationProfile::LOGO]);
    $grouped = collect($fields)->groupBy('group');
    // The organisation's tabs (as on its other pages); Users and Teams for those who manage them.
    $tabs = array_values(array_filter([
        ['route' => 'organisations.show', 'params' => ['organisationUid' => $organisation->uid], 'icon' => 'far fa-fw fa-list-alt', 'label' => 'Overview'],
        $canEdit ? ['route' => 'organisations.users.index', 'params' => ['organisationUid' => $organisation->uid], 'icon' => 'fas fa-fw fa-users', 'label' => 'Users'] : null,
        $canEdit ? ['route' => 'organisations.teams.index', 'params' => ['organisationUid' => $organisation->uid], 'icon' => 'fas fa-fw fa-users-cog', 'label' => 'Teams'] : null,
        ['route' => 'organisations.profile.show', 'params' => ['organisationUid' => $organisation->uid], 'icon' => 'fas fa-fw fa-id-card', 'label' => 'Profile'],
    ]));
@endphp

@extends($layout)

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make([$organisation->name]))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description($values['tagline'] ?? $organisation->name))

@section('content')
<div class="d-grid gap-3">

    {{-- Header --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            @if ($hasLogo)
                <img src="{{ route('organisations.logo', $organisation->uid) }}" alt="{{ $organisation->name }} logo" class="rounded border" style="width: 72px; height: 72px; object-fit: contain;">
            @else
                <span class="d-inline-flex align-items-center justify-content-center rounded bg-primary-subtle text-primary fs-3" style="width: 72px; height: 72px;">
                    {{ mb_strtoupper(mb_substr($organisation->name, 0, 1)) }}
                </span>
            @endif
            <div class="flex-grow-1" style="min-width: 12rem;">
                <h5 class="fs-5 mb-1">{{ $organisation->name }}</h5>
                @if (! empty($values['tagline']))<div class="text-muted small">{{ $values['tagline'] }}</div>@endif
                @if ($isPublic)
                    <a href="{{ route('organisations.public', $organisation->slug) }}" class="small" target="_blank" rel="noopener">
                        <i class="fas fa-fw fa-globe"></i> Public page
                    </a>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($membersUrl)
                    <a href="{{ $membersUrl }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-fw fa-users"></i> Members</a>
                @endif
                @if ($canEdit)
                    <a href="{{ route('organisations.profile.edit', $organisation->uid) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-fw fa-pen"></i> Edit profile</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7 d-grid gap-3 align-content-start">
            @foreach ($groups as $group => $title)
                @php($rows = collect($grouped[$group] ?? [])->filter(fn ($f) => ! empty($values[$f['id']])))
                @if ($rows->isNotEmpty())
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="fs-6 mb-3">{{ $title }}</h6>
                            <dl class="row small mb-0">
                                @foreach ($rows as $field)
                                    @php($value = $values[$field['id']])
                                    <dt class="col-sm-4 text-muted fw-normal">{{ $field['label'] }}</dt>
                                    <dd class="col-sm-8" style="white-space: pre-line;">@if ($field['type'] === 'url')<a href="{{ $value }}" target="_blank" rel="noopener nofollow">{{ $value }}</a>@elseif ($field['type'] === 'email')<a href="mailto:{{ $value }}">{{ $value }}</a>@else{{ $value }}@endif</dd>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                @endif
            @endforeach

            @if (collect($values)->only(collect($fields)->pluck('id'))->filter()->isEmpty())
                <div class="card border-0 shadow-sm"><div class="card-body text-muted small">
                    No details yet. @if ($canEdit)<a href="{{ route('organisations.profile.edit', $organisation->uid) }}">Add them</a>.@endif
                </div></div>
            @endif
        </div>

        {{-- Office bearers: members see contact details; the public page doesn't. --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fs-6 mb-3">Office bearers</h6>
                    @forelse ($organisation->officeBearers as $bearer)
                        <div class="py-2 border-bottom small">
                            <div class="text-muted">{{ $bearer->title }}</div>
                            <div class="fw-semibold">{{ $bearer->name }}</div>
                            @if ($bearer->email || $bearer->phone)
                                <div class="text-muted">
                                    @if ($bearer->email)<a href="mailto:{{ $bearer->email }}">{{ $bearer->email }}</a>@endif
                                    @if ($bearer->email && $bearer->phone) · @endif
                                    {{ $bearer->phone }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted small mb-0">None listed yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
