@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : config('organisation.layout');
@endphp

@extends($layout)

@php
    $tabs = [
        [
            'route' => 'organisations.show',
            'params' => [
                'organisationUid' => $organisation->uid,
            ],
            'icon' => 'far fa-fw fa-list-alt',
            'label' => 'Overview',
            // 'permission' => 'view-organisations',
        ],
        [
            'route' => 'organisations.users.index',
            'params' => [
                'organisationUid' => $organisation->uid,
            ],
            'icon' => 'fas fa-fw fa-users',
            'label' => 'Users',
            // 'permission' => 'view-organisations-users',
        ],
        [
            'route' => 'organisations.teams.index',
            'params' => [
                'organisationUid' => $organisation->uid,
            ],
            'icon' => 'fas fa-fw fa-users-cog',
            'label' => 'Teams',
            // 'permission' => 'view-teams'
        ]
    ];
@endphp

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make([($organisation->name ?? 'Organisation'), 'Organisation']))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('Show page of Organisation'))

@section('content')
<div class="">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            @include('organisation::components.entity-name-status', [
                'entity' => $organisation
            ])
        </div>
        <div class="d-flex align-items-center justify-content-center gap-2">
            <a href="{{ route('organisations.edit', $organisation->uid) }}" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-fw fa-edit"></i><span class="d-none d-md-inline-block ms-2">Edit</span>
            </a>
            <form action="{{ route('organisations.destroy', $organisation->uid) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-fw fa-trash"></i><span class="d-none d-md-inline-block ms-2">Delete</span>
                </button>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-md-9 d-flex flex-column align-items-start justify-content-center text-muted">
            <p class="mb-0"><strong>Name:</strong> {{ $organisation->name }}</p>
            <p class="mb-0"><strong>Description:</strong> {{ $organisation->description ?? 'N/A' }}</p>
        </div>
    </div>
</div>
@endsection