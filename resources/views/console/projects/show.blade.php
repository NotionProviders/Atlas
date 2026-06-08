@extends('console.layouts.app')

@section('title', $project->name)

@section('content')
<div class="console-page-header">
    <div>
        <h1>{{ $project->name }}</h1>
        @if ($project->client_name)
            <p class="console-muted">{{ $project->client_name }}</p>
        @endif
    </div>
    <div class="console-page-actions">
        <a href="{{ route('console.mappings.index', $project) }}" class="console-btn">Database mappings</a>
        <a href="{{ route('console.projects.edit', $project) }}" class="console-btn">Edit</a>
    </div>
</div>

@if ($project->notes)
    <p class="console-notes">{{ $project->notes }}</p>
@endif

<div class="console-snapshot-grid">
    @foreach (\App\Enums\SnapshotType::cases() as $type)
        @php
            $snapshot = $snapshotsByType[$type->value] ?? null;
            $canOpenCanonTable = $type === \App\Enums\SnapshotType::Canon && ($hasDatabaseMappings ?? false);
            $hasSnapshotData = $snapshot && ! $snapshot->isEmpty();
        @endphp
        <div class="console-snapshot-card">
            <h2>{{ $type->label() }}</h2>
            <p class="console-muted">
                @if ($hasSnapshotData)
                    Imported {{ $snapshot->imported_at?->diffForHumans() }}
                    · {{ $snapshot->nodes()->count() }} nodes
                @elseif ($canOpenCanonTable)
                    {{ $databaseMappingCount }} mapped {{ Str::plural('database', $databaseMappingCount) }}
                @else
                    No data imported
                @endif
            </p>
            <div class="console-snapshot-card-actions">
                @if ($hasSnapshotData)
                    <a href="{{ route('console.snapshots.show', [$project, $type->value, 'view' => 'atlas']) }}" class="console-btn console-btn-primary">Atlas</a>
                    <a href="{{ route('console.snapshots.show', [$project, $type->value, 'view' => 'table']) }}" class="console-btn">Table</a>
                @elseif ($canOpenCanonTable)
                    <a href="{{ route('console.snapshots.show', [$project, $type->value, 'view' => 'table']) }}" class="console-btn console-btn-primary">Table</a>
                @endif
                <form method="POST" action="{{ route('console.snapshots.import', [$project, $type->value]) }}" enctype="multipart/form-data" class="console-import-form">
                    @csrf
                    <label class="console-file-label">
                        <span class="console-btn">{{ $hasSnapshotData ? 'Re-import' : 'Import JSON' }}</span>
                        <input type="file" name="import_file" accept=".json,application/json" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
        </div>
    @endforeach
</div>

@if ($previewTypes->isNotEmpty() && $defaultPreviewType)
    <section class="console-atlas-preview" id="atlas-preview"
             data-default="{{ $defaultPreviewType->value }}">
        <div class="console-atlas-preview-header">
            <h2>Workspace preview</h2>
            <div class="console-atlas-preview-controls">
                <div class="console-snapshot-switcher" role="tablist" aria-label="Snapshot preview">
                    @foreach ($previewTypes as $type)
                        <button type="button"
                                class="console-snap-tab preview-tab {{ $type === $defaultPreviewType ? 'active' : '' }}"
                                data-type="{{ $type->value }}"
                                data-embed-url="{{ route('console.snapshots.embed', [$project, $type->value]) }}"
                                data-atlas-url="{{ route('console.snapshots.show', [$project, $type->value, 'view' => 'atlas']) }}"
                                role="tab"
                                aria-selected="{{ $type === $defaultPreviewType ? 'true' : 'false' }}">
                            {{ $type->label() }}
                        </button>
                    @endforeach
                </div>
                <a href="{{ route('console.snapshots.show', [$project, $defaultPreviewType->value, 'view' => 'atlas']) }}"
                   class="console-btn console-btn-primary preview-open-link"
                   target="_blank"
                   rel="noopener">
                    Open full atlas ↗
                </a>
            </div>
        </div>
        <div class="console-atlas-preview-frame-wrap">
            <iframe class="console-atlas-preview-frame preview-iframe"
                    title="Atlas preview"
                    src="{{ route('console.snapshots.embed', [$project, $defaultPreviewType->value]) }}"
                    loading="lazy"></iframe>
        </div>
    </section>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/project-preview.js') }}?v={{ @filemtime(public_path('js/project-preview.js')) ?: 1 }}" defer></script>
@endpush
