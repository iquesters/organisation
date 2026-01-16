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

@section(
    'page-title',
    \Iquesters\Foundation\Helpers\MetaHelper::make([
        'Team Users',
        $team->name,
        $organisation->name ?? 'Organisation'
    ])
)

@section(
    'meta-description',
    \Iquesters\Foundation\Helpers\MetaHelper::description(
        'Users assigned to team ' . $team->name .
        ' in organisation ' . ($organisation->name ?? '') . '.'
    )
)

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fs-6 text-muted">
        Total – {{ $users->count() }} User(s)
    </h5>

    <button class="btn btn-sm btn-outline-primary"
        data-bs-toggle="modal"
        data-bs-target="#addUserModal">
        <i class="fas fa-fw fa-user-plus"></i>
        <span class="ms-1">Add User</span>
    </button>
</div>

{{-- Users Table --}}
<div>
    <table class="table table-striped table-bordered" id="team-users-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Added On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        {{ \Iquesters\Foundation\Helpers\TimeHelper::displayDateTime($user->created_at) }}
                    </td>
                    <td>
                        <form method="POST"
                            action="{{ route('organisations.teams.users.removeUser', [
                                $organisation->uid,
                                $team->uid,
                                $user->uid
                            ]) }}"
                            class="d-inline">

                            @csrf
                            @method('DELETE')

                            <button class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Remove user from this team?')">
                                <i class="fas fa-fw fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Add User Modal --}}
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST"
            action="{{ route('organisations.teams.users.addUser', [
                $organisation->uid,
                $team->uid
            ]) }}"
            class="modal-content">

            @csrf

            <div class="modal-header">
                <h5 class="modal-title fs-6">
                    Add User to {{ $team->name }}
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Select user</option>
                        @foreach($availableUsers as $user)
                            <option value="{{ $user->uid }}">
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-sm btn-outline-dark" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button class="btn btn-sm btn-outline-primary">
                    Add User
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('#team-users-table').DataTable({
            responsive: true,
            order: [[0, 'asc']],
        });
    });
</script>
@endpush