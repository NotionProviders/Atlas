<section class="canonical-details-section">
    <div class="canonical-props-header">
        <h3>Properties ({{ $canonicalDatabase->properties->count() }})</h3>
        <p class="console-muted canonical-props-help">
            Types are stored as text for mapping reference. Change a rollup to a relation (or any type) using the dropdown, then Save.
        </p>
    </div>

    @if ($canonicalDatabase->properties->isEmpty())
        <p class="console-muted">No properties yet. Add one below or re-import from the template.</p>
    @else
        <div class="canonical-prop-list">
            @foreach ($canonicalDatabase->properties as $prop)
                <div class="canonical-prop-row">
                    <form method="POST"
                          action="{{ route('console.canonical.properties.update', [$canonicalDatabase, $prop]) }}"
                          class="canonical-prop-edit-form">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" value="{{ $prop->name }}" required class="canonical-prop-name-input" aria-label="Property name">
                        <select name="property_type" class="canonical-prop-type-select" aria-label="Property type">
                            @foreach ($propertyTypes as $type)
                                <option value="{{ $type }}" @selected($prop->property_type === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        @if ($prop->is_title)
                            <span class="console-tag console-tag-ok">Title</span>
                        @endif
                        <button type="submit" class="console-btn">Save</button>
                    </form>
                    @if (! $prop->is_title)
                        <form method="POST"
                              action="{{ route('console.canonical.properties.destroy', [$canonicalDatabase, $prop]) }}"
                              class="canonical-prop-delete-form"
                              onsubmit="return confirm('Remove this property?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="console-link-btn console-link-danger">Remove</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <details class="console-add-canonical canonical-add-property">
        <summary class="console-btn">+ Add property</summary>
        <form method="POST"
              action="{{ route('console.canonical.properties.store', $canonicalDatabase) }}"
              class="console-form console-add-canonical-form">
            @csrf
            <label>
                <span>Property name</span>
                <input type="text" name="name" required placeholder="e.g. Primary Contact">
            </label>
            <label>
                <span>Type</span>
                <select name="property_type" required>
                    @foreach ($propertyTypes as $type)
                        <option value="{{ $type }}" @selected($type === 'relation')>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="console-btn console-btn-primary">Add property</button>
        </form>
    </details>
</section>
