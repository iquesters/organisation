@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : config('organisation.layout');

    $tabs = [
        [
            'route' => 'organisations.teams.show',
            'params' => [
                'organisationUid' => $organisation->uid,
                'teamUid' => $team->uid,
            ],
            'icon' => 'far fa-fw fa-list-alt',
            'label' => 'Overview',
        ],
        [
            'route' => 'organisations.teams.users.index',
            'params' => [
                'organisationUid' => $organisation->uid,
                'teamUid' => $team->uid,
            ],
            'icon' => 'fas fa-fw fa-users',
            'label' => 'Users',
        ]
    ];
@endphp

@extends($layout)

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make([($team->name ?? 'Team'), 'Team']))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('Show page of Team'))

@section('content')
<div class="mb-2 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center justify-content-center gap-2">
        <h5 class="fs-6 text-muted">{{ $team->name }}</h5>
        <span class="badge badge-{{ $team->status }}">{{ ucfirst($team->status) }}</span>
    </div>
    <div>
        <a href="{{ route('organisations.teams.edit', ['organisationUid' => $organisation->uid, 'teamUid' => $team->uid]) }}" class="btn btn-sm btn-outline-dark">
            <i class="fas fa-fw fa-edit"></i><span class="d-none d-md-inline-block ms-2">Edit</span>
        </a>

        <form action="{{ route('organisations.teams.destroy', ['organisationUid' => $organisation->uid, 'teamUid' => $team->uid]) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Remove this team?')">
                <i class="fas fa-fw fa-trash"></i> <span class="d-none d-md-inline-block ms-2">Delete</span>
            </button>
        </form>
    </div>
</div>

<div class="mb-3">
    <p class="mb-0"><strong>Name:</strong> {{ $team->name }}</p>
    <p class="mb-0"><strong>Description:</strong> {{ $team->description ?? 'N/A' }}</p>
</div>

@if($team->metas->isNotEmpty())
<div class="card mb-3">
    <div class="card-header">
        Team Meta
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            @foreach($team->metas as $meta)
                <li class="list-group-item">
                    <strong>{{ $meta->meta_key }}:</strong> {{ $meta->meta_value }}
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif
@endsection