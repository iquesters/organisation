@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : \Iquesters\Foundation\Support\ConfProvider::from(\Iquesters\Foundation\Enums\Module::ORGANISATION)->organisation_layout;
@endphp

@extends($layout)

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make(['Edit profile', $organisation->name]))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('Edit the organisation profile'))

@section('content')
{{-- Full width, like every other page in the app layout. --}}
<div class="d-grid gap-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <a href="{{ route('organisations.profile.show', $organisation->uid) }}" class="small text-decoration-none"><i class="fas fa-fw fa-arrow-left"></i> {{ $organisation->name }}</a>
            @if ($hasLogo)
                <img src="{{ route('organisations.logo', $organisation->uid) }}" alt="Current logo" class="rounded border" style="width: 40px; height: 40px; object-fit: contain;">
            @endif
        </div>

        {{-- Form schema organisation-profile (sections, office bearers repeater, logo upload). --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @include('userinterface::components.form', [
                    'id' => \Iquesters\Organisation\Support\OrganisationProfile::FORM,
                    'entity_uid' => $organisation->uid,
                    'form_mode' => 'edit',
                    'formData' => json_encode($formData, JSON_UNESCAPED_UNICODE),
                ])
            </div>
        </div>
</div>
@endsection
