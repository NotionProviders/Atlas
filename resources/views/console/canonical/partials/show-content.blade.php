@php
    $projectQuery = $project ? ['project' => $project->slug] : [];
@endphp
<div class="canonical-show-panel">
    <section class="canonical-details-section">
        <h3>Notes</h3>
        <form method="POST" action="{{ route('console.canonical.update', $canonicalDatabase) }}" class="console-form canonical-notes-form">
            @csrf
            @method('PUT')
            @if ($project)
                <input type="hidden" name="project" value="{{ $project->slug }}">
            @endif
            <label>
                <span class="console-muted">Internal notes about this canonical database (mapping guidance, exceptions, etc.)</span>
                <textarea name="description" rows="4" placeholder="Optional notes">{{ old('description', $canonicalDatabase->description) }}</textarea>
            </label>
            <button type="submit" class="console-btn console-btn-primary">Save notes</button>
        </form>
    </section>

    @include('console.canonical.partials.details-meta')

    @if ($project)
        <section class="canonical-details-section">
            <h3>Project: {{ $project->name }}</h3>
            @if ($projectMappings->isNotEmpty())
                <p class="console-muted">Mapped from:
                    @foreach ($projectMappings as $pm)
                        <a href="{{ route('console.mappings.database.show', [$project, $pm->atlasNode]) }}" class="canonical-name-link">{{ $pm->atlasNode->label }}</a>@if (! $loop->last), @endif
                    @endforeach
                </p>
            @endif
            @include('console.mappings.partials.placement-form', [
                'project' => $project,
                'canonicalDatabase' => $canonicalDatabase,
                'placement' => $placement,
                'teamspaces' => $teamspaces,
            ])
        </section>
    @endif

    <section class="canonical-details-section">
        <h3>How this database is used</h3>
        @if ($canonicalDatabase->is_lookup)
            <p class="console-muted">Lookup and taxonomy databases hold shared reference values.</p>
        @else
            <p class="console-muted">Entity databases store primary business records for mapping client workspaces.</p>
        @endif
    </section>

    @include('console.canonical.partials.properties-editor')

    <form method="POST"
          action="{{ route('console.canonical.destroy', $canonicalDatabase) }}"
          class="canonical-details-danger"
          onsubmit="return confirm('Remove this canonical database? Existing project mappings will be deleted.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="console-link-btn console-link-danger">Remove from registry</button>
    </form>
</div>
