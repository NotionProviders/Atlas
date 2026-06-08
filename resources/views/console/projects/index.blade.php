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
        @foreach ($projects as $clientProject)
            <a href="{{ route('console.projects.show', $clientProject) }}" class="console-card">
                <h2>{{ $clientProject->name }}</h2>
                @if ($clientProject->client_name)
                    <p class="console-muted">{{ $clientProject->client_name }}</p>
                @endif
                <div class="console-snapshot-pills">
                    @foreach (\App\Enums\SnapshotType::cases() as $snapshotType)
                        @php $snap = $clientProject->snapshots->first(fn ($s) => $s->type === $snapshotType); @endphp
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
