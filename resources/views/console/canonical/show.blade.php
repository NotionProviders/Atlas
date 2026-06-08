@extends('console.layouts.app')

@section('title', $canonicalDatabase->name)

@section('breadcrumb')
    <span class="console-breadcrumb-sep">/</span>
    @if ($project)
        <a href="{{ route('console.projects.show', $project) }}" class="console-breadcrumb">{{ $project->name }}</a>
        <span class="console-breadcrumb-sep">/</span>
        <a href="{{ route('console.mappings.index', $project) }}" class="console-breadcrumb">Mappings</a>
        <span class="console-breadcrumb-sep">/</span>
    @else
        <a href="{{ route('console.canonical.index') }}" class="console-breadcrumb">Canonical databases</a>
        <span class="console-breadcrumb-sep">/</span>
    @endif
    <span class="console-breadcrumb">{{ $canonicalDatabase->name }}</span>
@endsection

@section('content')
@php
    $peekParams = $project ? ['canonicalDatabase' => $canonicalDatabase, 'project' => $project->slug] : ['canonicalDatabase' => $canonicalDatabase];
@endphp
<div class="console-page-header">
    <div>
        <h1>{{ $canonicalDatabase->name }}</h1>
        <p class="console-muted">
            @if ($canonicalDatabase->is_lookup)
                Lookup / taxonomy database
            @else
                Entity database
            @endif
            @if ($canonicalDatabase->is_custom)
                · <span class="console-tag console-tag-warn">Not in template</span>
            @endif
        </p>
    </div>
    <div class="console-page-actions">
        <a href="{{ route('console.canonical.peek-page', $peekParams) }}" class="console-btn">Center peek</a>
        @if ($project)
            <a href="{{ route('console.mappings.index', $project) }}" class="console-btn">Back to mappings</a>
        @else
            <a href="{{ route('console.canonical.index') }}" class="console-btn">Back to registry</a>
        @endif
    </div>
</div>

@include('console.canonical.partials.show-content')
@endsection

@push('scripts')
    @if ($project)
        <script src="{{ asset('js/mapping-combobox.js') }}?v={{ @filemtime(public_path('js/mapping-combobox.js')) ?: 1 }}" defer></script>
    @endif
@endpush
