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
<div class="console-page-header">
    <div>
        <h1>
            {{ $atlasNode->label }}
            @if ($mapping)
                → {{ $mapping->canonicalDatabase->name }}
            @endif
        </h1>
        <p class="console-muted">Client database migration mapping</p>
    </div>
    <div class="console-page-actions">
        <a href="{{ route('console.mappings.database.peek-page', [$project, $atlasNode]) }}" class="console-btn">Center peek</a>
        <a href="{{ route('console.mappings.index', $project) }}" class="console-btn">Back to mappings</a>
    </div>
</div>

<div class="canonical-show-panel">
    @include('console.mappings.partials.database-details')
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/mapping-combobox.js') }}?v={{ @filemtime(public_path('js/mapping-combobox.js')) ?: 1 }}" defer></script>
@endpush
