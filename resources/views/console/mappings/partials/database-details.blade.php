<div class="canonical-details">
    <dl class="canonical-details-meta">
        <div>
            <dt>Client database (Before)</dt>
            <dd>{{ $atlasNode->label }}</dd>
        </div>
        <div>
            <dt>Canonical target</dt>
            <dd>
                @include('console.mappings.partials.canonical-combobox', [
                    'node' => $atlasNode,
                    'mapping' => $mapping,
                    'canonicalDatabases' => $canonicalDatabases,
                ])
            </dd>
        </div>
    </dl>

    @if ($atlasNode->databaseProperties->isNotEmpty())
        <section class="canonical-details-section">
            <h3>Client properties</h3>
            <p class="console-muted mapping-props-hint">{{ $atlasNode->databaseProperties->pluck('name')->join(', ') }}</p>
        </section>
    @endif

    <section class="canonical-details-section">
        <h3>Migration details</h3>
        @if ($mapping)
            <form method="POST" action="{{ route('console.mappings.database.update', [$project, $atlasNode]) }}" class="console-form">
                @csrf
                @method('PUT')
                <label>
                    <span class="console-muted">Short notes</span>
                    <input type="text" name="notes" value="{{ old('notes', $mapping->notes) }}" placeholder="One-line summary">
                </label>
                <label>
                    <span class="console-muted">Migration plan</span>
                    <textarea name="migration_details" rows="6" placeholder="Property mapping notes, data cleanup, merge strategy…">{{ old('migration_details', $mapping->migration_details) }}</textarea>
                </label>
                <button type="submit" class="console-btn console-btn-primary">Save</button>
            </form>
        @else
            <p class="console-muted">Select or create a canonical target above to save migration notes.</p>
        @endif
    </section>

    @if ($mapping)
        <form method="POST"
              action="{{ route('console.mappings.database.destroy', [$project, $atlasNode]) }}"
              class="canonical-details-danger"
              onsubmit="return confirm('Remove this mapping?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="console-link-btn console-link-danger">Remove mapping</button>
        </form>
    @endif
</div>
