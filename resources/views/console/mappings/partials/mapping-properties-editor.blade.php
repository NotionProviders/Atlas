<section class="canonical-details-section">
    <div class="canonical-props-header">
        <h3>Migration schema ({{ $mapping->properties->count() }})</h3>
        <p class="console-muted canonical-props-help">
            Properties copied from the canonical template are locked and cannot be removed. Add custom properties or merge client properties above.
        </p>
    </div>

    @if ($mapping->properties->isEmpty())
        <p class="console-muted">No properties yet. Re-select the canonical target if the template should have properties.</p>
    @else
        <div class="canonical-prop-list">
            @foreach ($mapping->properties as $prop)
                <div class="canonical-prop-row @if ($prop->is_locked) mapping-prop-locked @endif">
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
                            <span class="console-tag" title="Copied from canonical template">Template</span>
                        @elseif ($prop->source === 'client')
                            <span class="console-tag console-tag-warn" title="Merged from client database">Client</span>
                        @else
                            <span class="console-tag console-tag-ok">Custom</span>
                        @endif
                        @if ($prop->is_title)
                            <span class="console-tag console-tag-ok">Title</span>
                        @endif
                        <input type="text"
                               name="merge_notes"
                               value="{{ old('merge_notes', $prop->merge_notes) }}"
                               placeholder="Merge notes"
                               class="mapping-prop-notes-input"
                               aria-label="Merge notes">
                        <button type="submit" class="console-btn">Save</button>
                    </form>
                    @if (! $prop->is_locked && ! $prop->is_title)
                        <form method="POST"
                              action="{{ route('console.mappings.database.properties.destroy', [$project, $atlasNode, $prop]) }}"
                              class="canonical-prop-delete-form"
                              onsubmit="return confirm('Remove this property from the migration schema?');">
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
