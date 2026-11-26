{{-- Record section (Organisations table): the organisation's other pages, as plain links
     (no tabs: the sidebar is the menu, bug #28). --}}
<div class="d-flex flex-wrap gap-3 small">
    <a href="{{ route('organisations.profile.show', $organisation->uid) }}"><i class="fas fa-fw fa-id-card"></i> Profile</a>
    @if ($organisation->slug && \Iquesters\Organisation\Support\OrganisationProfile::isPublic($organisation))
        <a href="{{ route('organisations.public', $organisation->uid) }}" target="_blank" rel="noopener"><i class="fas fa-fw fa-globe"></i> Public page</a>
    @endif
</div>
