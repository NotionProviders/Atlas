@extends('console.layouts.app')

@section('title', 'Mapping — '.$atlasNode->label)

@section('breadcrumb')
    <span class="console-breadcrumb-sep">/</span>
    <a href="{{ route('console.projects.show', $project) }}" class="console-breadcrumb">{{ $project->name }}</a>
    <span class="console-breadcrumb-sep">/</span>
    <a href="{{ route('console.mappings.index', $project) }}" class="console-breadcrumb">Mappings</a>
    <span class="console-breadcrumb-sep">/</span>
    <span class="console-breadcrumb">{{ $atlasNode->label }}</span>
@endsection

@section('content')
<div class="mapping-page-toolbar">
    <h1 class="mapping-page-title">
        {{ $atlasNode->label }}@if ($mapping)<span class="mapping-page-arrow">→</span>{{ $mapping->canonicalDatabase->name }}@endif
    </h1>
    <div class="mapping-page-actions">
        <a href="{{ route('console.mappings.database.peek-page', [$project, $atlasNode]) }}" class="console-btn console-btn-sm">Center peek</a>
        <a href="{{ route('console.mappings.index', $project) }}" class="console-btn console-btn-sm">Back</a>
    </div>
</div>

@include('console.mappings.partials.database-details')

<div id="mapping-modal"
     class="canonical-modal hidden"
     role="dialog"
     aria-modal="true"
     data-index-url="{{ route('console.mappings.database.show', [$project, $atlasNode]) }}">
    <button type="button" class="canonical-modal-backdrop" aria-label="Close details"></button>
    <div class="canonical-modal-slot" id="mapping-modal-slot"></div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/mapping-combobox.js') }}?v={{ @filemtime(public_path('js/mapping-combobox.js')) ?: 1 }}" defer></script>
    <script src="{{ asset('js/mapping-modal.js') }}?v={{ @filemtime(public_path('js/mapping-modal.js')) ?: 1 }}" defer></script>
@endpush
