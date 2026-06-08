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
@include('console.mappings.partials.database-details', ['peekMode' => false])

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
