@extends('console.layouts.app')

@section('title', 'Canonical databases')

@section('breadcrumb')
    <span class="console-breadcrumb-sep">/</span>
    <span class="console-breadcrumb">Canonical databases</span>
@endsection

@section('content')
<div class="console-page-header">
    <div>
        <h1>Canonical database registry</h1>
        <p class="console-muted">
            Master pool of target databases for mapping client workspaces.
            Multiple client databases can map to the same canonical database.
        </p>
    </div>
</div>

<div class="console-canonical-stats">
    <span class="console-tag console-tag-ok">{{ $entityDatabases->count() }} entity databases</span>
    <span class="console-tag">{{ $lookupDatabases->count() }} lookup / taxonomy databases</span>
    <span class="console-tag">{{ $canonicalDatabases->sum(fn ($d) => $d->properties->count()) }} properties total</span>
</div>

<details class="console-add-canonical">
    <summary class="console-btn">+ Add canonical database manually</summary>
    <form method="POST" action="{{ route('console.canonical.store') }}" class="console-form console-add-canonical-form">
        @csrf
        <label>
            <span>Name</span>
            <input type="text" name="name" required placeholder="e.g. Companies">
        </label>
        <label>
            <span>Description</span>
            <textarea name="description" rows="2"></textarea>
        </label>
        <fieldset class="console-property-fieldset">
            <legend>Standard properties</legend>
            @for ($i = 0; $i < 5; $i++)
                <div class="console-property-row">
                    <input type="text" name="properties[{{ $i }}][name]" placeholder="Property name">
                    <input type="text" name="properties[{{ $i }}][property_type]" placeholder="Type">
                </div>
            @endfor
        </fieldset>
        <button type="submit" class="console-btn console-btn-primary">Create</button>
    </form>
</details>

@if ($canonicalDatabases->isEmpty())
    <p class="console-muted">No canonical databases yet. Import the template with <code>php artisan atlas:import-canonical-template path/to/export</code>.</p>
@else
    @if ($entityDatabases->isNotEmpty())
        <h2 class="console-section-title">Entity databases</h2>
        @include('console.canonical.partials.table', ['databases' => $entityDatabases, 'showNotes' => true])
    @endif

    @if ($lookupDatabases->isNotEmpty())
        <h2 class="console-section-title">Lookup & taxonomy databases</h2>
        @include('console.canonical.partials.table', ['databases' => $lookupDatabases])
    @endif
@endif

<div id="canonical-modal"
     class="canonical-modal hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="canonical-peek-title"
     data-index-url="{{ route('console.canonical.index') }}"
     @if ($openPeek)
         data-open-slug="{{ $openPeek->slug }}"
         data-open-panel-url="{{ route('console.canonical.panel', $openPeek) }}"
         data-open-peek-url="{{ route('console.canonical.peek-page', $openPeek) }}"
     @endif>
    <button type="button" class="canonical-modal-backdrop" aria-label="Close details"></button>
    <div class="canonical-modal-slot" id="canonical-modal-slot"></div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/canonical-registry.js') }}?v={{ @filemtime(public_path('js/canonical-registry.js')) ?: 1 }}" defer></script>
@endpush
