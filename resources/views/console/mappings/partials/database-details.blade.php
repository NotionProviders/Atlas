<div class="mapping-workspace">
    @include('console.mappings.partials.mapping-row-header', [
        'peekMode' => $peekMode ?? false,
    ])

    @if ($mapping)
        @include('console.mappings.partials.mapping-properties-editor')

        <details class="mapping-notes-details">
            <summary>Migration notes</summary>
            <form method="POST" action="{{ route('console.mappings.database.update', [$project, $atlasNode]) }}" class="console-form">
                @csrf
                @method('PUT')
                <label>
                    <span class="console-muted">Short notes</span>
                    <input type="text" name="notes" value="{{ old('notes', $mapping->notes) }}" placeholder="One-line summary">
                </label>
                <label>
                    <span class="console-muted">Migration plan</span>
                    <textarea name="migration_details" rows="4" placeholder="Data cleanup, merge strategy, exceptions…">{{ old('migration_details', $mapping->migration_details) }}</textarea>
                </label>
                <input type="hidden" name="project_teamspace_id" value="{{ $mapping->project_teamspace_id }}">
                <input type="hidden" name="placement_notes" value="{{ $mapping->placement_notes }}">
                <button type="submit" class="console-btn console-btn-primary">Save notes</button>
            </form>
        </details>

        <form method="POST"
              action="{{ route('console.mappings.database.destroy', [$project, $atlasNode]) }}"
              class="canonical-details-danger"
              onsubmit="return confirm('Remove this mapping?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="console-link-btn console-link-danger">Remove mapping</button>
        </form>
    @else
        <p class="console-muted mapping-unmapped-hint">Select or create a canonical target to begin building the migration schema.</p>
    @endif
</div>
