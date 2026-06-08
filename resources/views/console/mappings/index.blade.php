@extends('console.layouts.app')

@section('title', 'Database mappings — '.$project->name)

@section('breadcrumb')
    <span class="console-breadcrumb-sep">/</span>
    <a href="{{ route('console.projects.show', $project) }}" class="console-breadcrumb">{{ $project->name }}</a>
    <span class="console-breadcrumb-sep">/</span>
    <span class="console-breadcrumb">Mappings</span>
@endsection

@section('content')
<div class="console-page-header">
    <div>
        <h1>Database mappings</h1>
        <p class="console-muted">Map each client database (Before) to a canonical target. New canonical databases created here appear in the Canonical table until assigned to a teamspace.</p>
    </div>
    <div class="console-page-actions">
        <a href="{{ route('console.canonical.index') }}" class="console-btn">Global registry</a>
        <a href="{{ route('console.projects.show', $project) }}" class="console-btn">Back to project</a>
    </div>
</div>

@if (!$beforeSnapshot || $beforeSnapshot->isEmpty())
    <p class="console-muted">Import a <strong>Before</strong> snapshot first to see client databases here.</p>
@elseif ($clientDatabases->isEmpty())
    <p class="console-muted">No databases found in the Before snapshot.</p>
@else
    <h2 class="console-section-title">Before → Canonical</h2>
    <table class="workspace-table mapping-table">
        <thead>
            <tr>
                <th class="mapping-col-client">Client database (Before)</th>
                <th class="mapping-col-arrow"></th>
                <th class="mapping-col-canonical">Maps to (Canonical)</th>
                <th class="mapping-col-info" aria-label="Details"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clientDatabases as $node)
                @php $mapping = $mappingsByNodeId->get($node->id); @endphp
                @include('console.mappings.partials.client-row', [
                    'node' => $node,
                    'mapping' => $mapping,
                    'canonicalDatabases' => $canonicalDatabases,
                ])
            @endforeach
        </tbody>
    </table>

    <h2 class="console-section-title">Canonical table</h2>
    <p class="console-muted mappings-canonical-intro">Reference list of canonical templates used in this project. Migration work happens in the Before → Canonical rows above.</p>

    @if ($projectCanonicalRows->isEmpty())
        <p class="console-muted">No mappings yet. Select or create a canonical target above.</p>
    @else
        <table class="workspace-table mapping-table canonical-project-table">
            <thead>
                <tr>
                    <th>Canonical database</th>
                    <th>Source</th>
                    <th>Client mappings</th>
                    <th>Template</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projectCanonicalRows as $row)
                    @include('console.mappings.partials.canonical-row', ['row' => $row])
                @endforeach
            </tbody>
        </table>
    @endif
@endif

<div id="mapping-modal"
     class="canonical-modal hidden"
     role="dialog"
     aria-modal="true"
     data-index-url="{{ route('console.mappings.index', $project) }}"
     @if ($openDatabaseNode)
         data-open-panel-url="{{ route('console.mappings.database.panel', [$project, $openDatabaseNode]) }}"
         data-open-peek-url="{{ route('console.mappings.database.peek-page', [$project, $openDatabaseNode]) }}"
     @endif>
    <button type="button" class="canonical-modal-backdrop" aria-label="Close details"></button>
    <div class="canonical-modal-slot" id="mapping-modal-slot"></div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/mapping-combobox.js') }}?v={{ @filemtime(public_path('js/mapping-combobox.js')) ?: 1 }}" defer></script>
    <script src="{{ asset('js/mapping-modal.js') }}?v={{ @filemtime(public_path('js/mapping-modal.js')) ?: 1 }}" defer></script>
@endpush
