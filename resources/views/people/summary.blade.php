{{-- Record section (People and roles): who the person is. --}}
<dl class="row small mb-0">
    <dt class="col-sm-4 text-muted fw-normal">Name</dt>
    <dd class="col-sm-8">{{ $person['name'] }}</dd>
    <dt class="col-sm-4 text-muted fw-normal">Email</dt>
    <dd class="col-sm-8">{{ $person['email'] }}</dd>
    <dt class="col-sm-4 text-muted fw-normal">Organisation</dt>
    <dd class="col-sm-8">{{ $person['organisation'] }}</dd>
    <dt class="col-sm-4 text-muted fw-normal">Role</dt>
    <dd class="col-sm-8">{{ $person['role_label'] }}</dd>
    <dt class="col-sm-4 text-muted fw-normal">Since</dt>
    <dd class="col-sm-8">{{ $person['since'] }}</dd>
</dl>
@if ($person['can_manage'] !== '1')
    <p class="small text-muted mt-2 mb-0">
        <i class="fas fa-fw fa-circle-info"></i> This is you: another organisation admin (or a super-admin) can change your role.
    </p>
@endif
