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
            'params' => ['organisationUid' => $organisation->uid],
            'icon' => 'far fa-fw fa-list-alt',
            'label' => 'Overview',
        ],
        [
            'route' => 'organisations.users.index',
            'params' => ['organisationUid' => $organisation->uid],
            'icon' => 'fas fa-fw fa-users',
            'label' => 'Users',
        ],
        [
            'route' => 'organisations.teams.index',
            'params' => ['organisationUid' => $organisation->uid],
            'icon' => 'fas fa-fw fa-users-cog',
            'label' => 'Teams',
        ]
    ];
@endphp

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make(['Teams', ($organisation->name ?? 'Organisation')]))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('List of teams in the organisation ' . ($organisation->name ?? '') . '. View them, add and more.'))

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fs-6 text-muted">Total {{ $teams->count() }} Teams</h5>

    <a class="btn btn-outline-primary btn-sm d-flex align-items-center shadow-sm rounded"
       href="{{ route('organisations.teams.create', $organisation->uid) }}">
        <i class="fas fa-fw fa-plus"></i>
        <span class="d-none d-md-inline-block ms-2">Team</span>
    </a>
</div>

<table id="organisation-teams-table" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($teams as $team)
            <tr>
                <td>
                    <a href="{{ route('organisations.teams.show', ['organisationUid' => $organisation->uid, 'teamUid' => $team->uid]) }}" 
                        class="text-decoration-none">
                        {{ $team->name }}
                    </a>
                </td>
                <td>{{ Str::limit($team->description, 50) }}</td>
                <td>
                    <span class="badge badge-{{ $team->status }}">{{ ucfirst($team->status) }}</span>
                </td>
                <td>
                    {{ \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($team->created_at) }}
                </td>
                <td>
                    <form action="{{ route('organisations.teams.destroy', ['organisationUid' => $organisation->uid, 'teamUid' => $team->uid]) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Remove this team?')">
                            <i class="fas fa-fw fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@push('scripts')
<script>
    $(document).ready(function () {
        $('#organisation-teams-table').DataTable({
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [4] }
            ]
        });
    });
</script>
@endpush

@endsection