@extends('console.layouts.app')

@section('title', 'Projects')

@section('content')
<div class="console-page-header">
    <h1>Client Projects</h1>
    <a href="{{ route('console.projects.create') }}" class="console-btn console-btn-primary">New project</a>
</div>

@if ($projects->isEmpty())
    <p class="console-muted">No projects yet. Create one to start mapping a client workspace.</p>
@else
    <div class="console-card-list">
        @foreach ($projects as $project)
            <a href="{{ route('console.projects.show', $project) }}" class="console-card">
                <h2>{{ $project->name }}</h2>
                @if ($project->client_name)
                    <p class="console-muted">{{ $project->client_name }}</p>
                @endif
                <div class="console-snapshot-pills">
                    @foreach (\App\Enums\SnapshotType::cases() as $snapshotType)
                        @php $snap = $project->snapshots->first(fn ($s) => $s->type === $snapshotType); @endphp
                        <span class="console-pill {{ $snap && !$snap->isEmpty() ? 'console-pill-ok' : 'console-pill-empty' }}">
                            {{ $snapshotType->label() }}
                        </span>
                    @endforeach
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
