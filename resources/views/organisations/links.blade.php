{{-- Record section (Organisations table): the organisation's tabs, as on its other pages. --}}
<ul class="nav nav-tabs small">
    <li class="nav-item">
        <span class="nav-link active" aria-current="page"><i class="far fa-fw fa-list-alt"></i> Overview</span>
    </li>
    @if ($canAdminister)
        <li class="nav-item">
            <a class="nav-link" href="{{ $peopleUrl }}"><i class="fas fa-fw fa-users"></i> People and roles</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ $teamsUrl }}"><i class="fas fa-fw fa-users-cog"></i> Teams</a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link" href="{{ route('organisations.profile.show', $organisation->uid) }}"><i class="fas fa-fw fa-id-card"></i> Profile</a>
    </li>
    @if ($organisation->slug && \Iquesters\Organisation\Support\OrganisationProfile::isPublic($organisation))
        <li class="nav-item ms-auto">
            <a class="nav-link" href="{{ route('organisations.public', $organisation->slug) }}" target="_blank" rel="noopener"><i class="fas fa-fw fa-globe"></i> Public page</a>
        </li>
    @endif
</ul>
