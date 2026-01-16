@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : config('organisation.layout');

    $isEdit = isset($team);
@endphp

@extends($layout)

@section('page-title', $isEdit ? 'Edit Team' : 'Create Team')

@section('content')
<div>
    <h6 class="text-muted">
        {{ $isEdit ? 'Edit Team' : 'Create Team' }} In – {{ $organisation->name }}
    </h6>
</div>

<div>
    <form
        method="POST"
        action="{{ $isEdit
            ? route('organisations.teams.update', [$organisation->uid, $team->uid])
            : route('organisations.teams.store', $organisation->uid)
        }}"
    >
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <!-- Team Name -->
        <div class="mb-3">
            <label class="form-label">Team Name</label>
            <input
                type="text"
                name="name"
                class="form-control"
                value="{{ old('name', $team->name ?? '') }}"
                required
            >
            @error('name')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea
                name="description"
                class="form-control"
                rows="4"
            >{{ old('description', $team->description ?? '') }}</textarea>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('organisations.teams.index', $organisation->uid) }}"
               class="btn btn-sm btn-outline-dark">
                Cancel
            </a>

            <button class="btn btn-sm btn-outline-primary" type="submit">
                {{ $isEdit ? 'Update' : 'Create' }}
            </button>
        </div>
    </form>
</div>
@endsection