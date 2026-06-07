@extends('console.layouts.app')

@section('title', $snapshotType->label().' — '.$project->name)

@section('breadcrumb')
    <span class="console-breadcrumb-sep">/</span>
    <span class="console-breadcrumb">{{ $snapshotType->label() }}</span>
@endsection

@section('content')
@include('console.snapshots.partials.toolbar', ['view' => $view ?? 'atlas'])

<div class="console-empty-snapshot">
    <h2>{{ $snapshotType->label() }} snapshot is empty</h2>
    <p class="console-muted">Import a workspace JSON file to populate this snapshot.</p>
    <form method="POST" action="{{ route('console.snapshots.import', [$project, $snapshotType->value]) }}" enctype="multipart/form-data" class="console-import-form console-import-form-large">
        @csrf
        <label class="console-file-label">
            <span class="console-btn console-btn-primary">Choose JSON file</span>
            <input type="file" name="import_file" accept=".json,application/json" required onchange="this.form.submit()">
        </label>
    </form>
    <p class="console-muted">Or run: <code>php artisan atlas:import-snapshot {{ $project->slug }} {{ $snapshotType->value }} path/to/file.json</code></p>
</div>
@endsection
