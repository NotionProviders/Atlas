<div class="canonical-show-panel">
    <section class="canonical-details-section">
        <h3>Notes</h3>
        <form method="POST" action="{{ route('console.canonical.update', $canonicalDatabase) }}" class="console-form canonical-notes-form">
            @csrf
            @method('PUT')
            <label>
                <span class="console-muted">Internal notes about this canonical database (mapping guidance, exceptions, etc.)</span>
                <textarea name="description" rows="4" placeholder="Optional notes">{{ old('description', $canonicalDatabase->description) }}</textarea>
            </label>
            <button type="submit" class="console-btn console-btn-primary">Save notes</button>
        </form>
    </section>

    @include('console.canonical.partials.details-meta')

    <section class="canonical-details-section">
        <h3>How this database is used</h3>
        @if ($canonicalDatabase->is_lookup)
            <p class="console-muted">Lookup and taxonomy databases hold shared reference values. Project migrations copy these properties into their own schema — changes here do not alter in-progress migrations.</p>
        @else
            <p class="console-muted">Entity databases store primary business records. Use this registry as the template reference when mapping client databases — each project migration keeps its own finalized property schema.</p>
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
