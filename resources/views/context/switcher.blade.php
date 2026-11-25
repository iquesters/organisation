{{-- The organisation the user works in. One organisation: its name (links to the profile). More: a menu to switch. --}}
<div class="dropdown organisation-switcher">
    @if ($available->count() === 1 && ! $canClear)
        <a href="{{ route('organisations.profile.show', $current->uid) }}" class="btn btn-sm btn-light d-flex align-items-center gap-1 text-truncate" style="max-width: 14rem;" title="{{ $current->name }}">
            <i class="fas fa-fw fa-building" aria-hidden="true"></i>
            <span class="d-none d-md-inline text-truncate">{{ $current->name }}</span>
        </a>
    @else
        <button type="button" class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-1" style="max-width: 14rem;"
                data-bs-toggle="dropdown" aria-expanded="false" title="{{ $current?->name ?? 'All organisations' }}">
            <i class="fas fa-fw fa-building" aria-hidden="true"></i>
            <span class="d-none d-md-inline text-truncate">{{ $current?->name ?? 'All organisations' }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end small" style="max-height: 60vh; overflow-y: auto;">
            <li><h6 class="dropdown-header">Working in</h6></li>
            @foreach ($available as $organisation)
                <li>
                    <form method="POST" action="{{ route('organisations.context.update') }}">
                        @csrf
                        <input type="hidden" name="organisation_uid" value="{{ $organisation->uid }}">
                        <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center gap-2 @if ($current?->uid === $organisation->uid) active @endif"
                                @if ($current?->uid === $organisation->uid) aria-current="true" @endif>
                            <span class="text-truncate">{{ $organisation->name }}</span>
                            @if ($current?->uid === $organisation->uid)<i class="fas fa-check" aria-hidden="true"></i>@endif
                        </button>
                    </form>
                </li>
            @endforeach
            @if ($canClear)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('organisations.context.destroy') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item @if (! $current) active @endif">All organisations</button>
                    </form>
                </li>
            @endif
            @if ($current)
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('organisations.profile.show', $current->uid) }}"><i class="fas fa-fw fa-id-card"></i> Organisation profile</a></li>
            @endif
        </ul>
    @endif
</div>
