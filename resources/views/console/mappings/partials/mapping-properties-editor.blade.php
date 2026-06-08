<section class="mapping-schema-section">
    <div class="mapping-schema-header">
        <h3>Migration schema <span class="console-muted">({{ $mapping->properties->count() }})</span></h3>
    </div>

    @if ($mapping->properties->isEmpty())
        <p class="console-muted">No properties yet. Re-select the canonical target if the template should have properties.</p>
    @else
        <div class="canonical-prop-list mapping-schema-list">
            @foreach ($mapping->properties as $prop)
                <div class="canonical-prop-row">
                    <form method="POST"
                          action="{{ route('console.mappings.database.properties.update', [$project, $atlasNode, $prop]) }}"
                          class="canonical-prop-edit-form">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" value="{{ $prop->name }}" required class="canonical-prop-name-input" aria-label="Property name">
                        <select name="property_type" class="canonical-prop-type-select" aria-label="Property type">
                            @foreach ($propertyTypes as $type)
                                <option value="{{ $type }}" @selected($prop->property_type === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        @if ($prop->source === 'canonical')
                            <span class="console-tag" title="Copied from canonical template">Canonical</span>
                        @elseif ($prop->source === 'client')
                            <span class="console-tag console-tag-warn" title="Merged from client database">Client</span>
                        @else
                            <span class="console-tag console-tag-ok">Custom</span>
                        @endif
                        <input type="text"
                               name="merge_notes"
                               value="{{ old('merge_notes', $prop->merge_notes) }}"
                               placeholder="Merge notes"
                               class="mapping-prop-notes-input"
                               aria-label="Merge notes">
                        <button type="submit" class="console-btn">Save</button>
                    </form>
                    <form method="POST"
                          action="{{ route('console.mappings.database.properties.destroy', [$project, $atlasNode, $prop]) }}"
                          class="canonical-prop-delete-form"
                          onsubmit="return confirm('Remove this property from the migration schema?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="console-link-btn console-link-danger">Remove</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    <details class="console-add-canonical canonical-add-property">
        <summary class="console-btn">+ Add property</summary>
        <form method="POST"
              action="{{ route('console.mappings.database.properties.store', [$project, $atlasNode]) }}"
              class="console-form console-add-canonical-form">
            @csrf
            <label>
                <span>Property name</span>
                <input type="text" name="name" required placeholder="e.g. Legacy ID">
            </label>
            <label>
                <span>Type</span>
                <select name="property_type" required>
                    @foreach ($propertyTypes as $type)
                        <option value="{{ $type }}" @selected($type === 'rich_text')>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="console-btn console-btn-primary">Add property</button>
        </form>
    </details>
</section>
