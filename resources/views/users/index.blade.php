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

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make([($organisation->name ?? 'Organisation'), 'Users']))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('List of users in the organisation ' . ($organisation->name ?? '') . '. View them, add project, and more.'))

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 text-muted">Total {{ $users->count() }} Users</h5>
        <div class="d-flex justify-content-center align-items-center gap-2">
            <a class="btn btn-outline-primary btn-sm d-flex align-items-center shadow-sm rounded"
                href="{{ route('organisations.users.create', $organisation->uid) }}">
                <i class="fas fa-fw fa-plus"></i><span class="d-none d-md-inline-block ms-2">User</span>
            </a>
            @if (Auth::user()->hasRole('super-admin'))
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-fw fa-user-plus"></i><span class="d-none d-md-inline-block ms-2">Existing User</span>
                </button>
            @endif
        </div>
    </div>
    <table id="organisation-users-table" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Organisations</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @foreach ($user->organisations as $org)
                            <span class="badge badge-draft">{{ $org->name }}</span>
                        @endforeach
                    </td>
                    <td>{{ \Iquesters\Foundation\Helpers\TimeHelper::displayDateTime($user->created_at) }}</td>
                    <td>
                        <form action="{{ route('organisations.users.removeUser', ['organisationUid' => $organisation->uid, 'userUid' => $user->uid]) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Are you sure you want to remove this user?')"><i class="fas fa-fw fa-trash me-1"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-6">Add Existing User to {{ $organisation->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('organisations.users.addUser', $organisation->uid) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="" disabled selected>Select User</option>
                            @foreach($availableUsers as $user)
                                <option value="{{ $user->uid }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#organisation-users-table').DataTable({
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [3] } // Disable sorting for actions column
            ]
        });
    });
</script>
@endpush
@endsection