@extends('console.layouts.app')

@section('title', $snapshotType->label().' — '.$project->name)

@section('breadcrumb')
    <span class="console-breadcrumb-sep">/</span>
    <span class="console-breadcrumb">{{ $snapshotType->label() }}</span>
@endsection

@section('content')
@include('console.snapshots.partials.toolbar', ['view' => 'atlas'])

<div class="console-atlas-wrap">
    @include('atlas.partials.map', ['kicker' => $project->name.' · '.$snapshotType->label()])
</div>
@endsection

@push('head')
<link rel="stylesheet" href="{{ asset('css/atlas.css') }}?v={{ @filemtime(public_path('css/atlas.css')) ?: 1 }}">
<style>
    .console-body { background: #060a14; }
    .console-main { padding: 0; max-width: none; }
    .console-atlas-wrap { min-height: calc(100vh - 52px); }
    .console-atlas-wrap #app { min-height: calc(100vh - 52px); }
    .console-snapshot-toolbar { position: relative; z-index: 20; padding: 0.75rem 1.25rem; background: #0b1220; border-bottom: 1px solid #1e293b; }
</style>
@endpush

@push('scripts')
<script>{!! $atlasConfigScript !!}</script>
<script src="{{ asset('js/atlas.js') }}?v={{ @filemtime(public_path('js/atlas.js')) ?: 1 }}" defer></script>
@endpush
