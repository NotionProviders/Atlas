@extends('console.layouts.app')

@section('title', $snapshotType->label().' — '.$project->name)

@section('breadcrumb')
    <span class="console-breadcrumb-sep">/</span>
    <span class="console-breadcrumb">{{ $snapshotType->label() }}</span>
@endsection

@section('content')
@include('console.snapshots.partials.toolbar', ['view' => 'table'])

<div class="console-page-header">
    <div>
        <h1>Canonical table</h1>
        <p class="console-muted">Reference list of canonical templates used in this project. Open client mappings to finalize property schemas and teamspace placement.</p>
    </div>
    <div class="console-page-actions">
        <a href="{{ route('console.mappings.index', $project) }}" class="console-btn">Database mappings</a>
        <a href="{{ route('console.projects.show', $project) }}" class="console-btn">Back to project</a>
    </div>
</div>

@if ($projectCanonicalRows->isEmpty())
    <p class="console-muted">No canonical databases mapped yet. <a href="{{ route('console.mappings.index', $project) }}">Create a mapping</a> to populate this table.</p>
@else
    <table class="workspace-table mapping-table canonical-project-table">
        <thead>
            <tr>
                <th>Canonical database</th>
                <th>Source</th>
                <th>Client mappings</th>
                <th class="mapping-col-actions" aria-label="Template"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projectCanonicalRows as $row)
                @include('console.mappings.partials.canonical-row', ['row' => $row])
            @endforeach
        </tbody>
    </table>
@endif

<div id="mapping-modal"
     class="canonical-modal hidden"
     role="dialog"
     aria-modal="true"
     data-index-url="{{ route('console.snapshots.show', [$project, 'canon', 'view' => 'table']) }}">
    <button type="button" class="canonical-modal-backdrop" aria-label="Close details"></button>
    <div class="canonical-modal-slot" id="mapping-modal-slot"></div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/mapping-modal.js') }}?v={{ @filemtime(public_path('js/mapping-modal.js')) ?: 1 }}" defer></script>
@endpush
