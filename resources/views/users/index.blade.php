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
            <button class="btn btn-outline-primary btn-sm d-flex align-items-center shadow-sm rounded"
                data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-fw fa-plus"></i><span class="d-none d-md-inline-block ms-2">User</span>
            </button>
            {{-- @if (Auth::user()->hasRole('super-admin'))
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-fw fa-user-plus"></i><span class="d-none d-md-inline-block ms-2">Existing User</span>
                </button>
            @endif --}}
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

{{-- <!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-6">Create New User in {{ $organisation->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <input type="hidden" name="organisation_uid" value="{{ $organisation->uid }}">
                <div class="modal-body">
                    <!-- User Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')
                        <span class="text-danger text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                        <span class="text-danger text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                        @error('password')
                        <span class="text-danger text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Roles -->
                    <div class="mb-4">
                        <label class="form-label">Assign Roles</label>
                        <div class="row">
                            @foreach($roles as $role)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="role-{{ $role->name }}" name="roles[]" value="{{ $role->name }}">
                                    <label class="form-check-label" for="role-{{ $role->name }}">{{ $role->name }}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @error('roles')
                        <span class="text-danger text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-outline-primary @cannot('create-users') disabled @endcannot"
                        @cannot('create-users') disabled @endcannot>Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
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
</div> --}}

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