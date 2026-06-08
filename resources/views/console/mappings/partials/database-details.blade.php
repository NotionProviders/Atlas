<div class="mapping-workspace">
    @if ($mapping)
        <div class="mapping-workspace-top">
            <div class="mapping-workspace-fields">
                <div class="mapping-workspace-field">
                    <span class="mapping-workspace-label">Before</span>
                    <strong>{{ $atlasNode->label }}</strong>
                </div>
                <div class="mapping-workspace-field mapping-workspace-field-grow">
                    <span class="mapping-workspace-label">Canonical</span>
                    @include('console.mappings.partials.canonical-combobox', [
                        'node' => $atlasNode,
                        'mapping' => $mapping,
                        'canonicalDatabases' => $canonicalDatabases,
                    ])
                </div>
                <div class="mapping-workspace-field">
                    <span class="mapping-workspace-label">Teamspace</span>
                    @include('console.mappings.partials.mapping-teamspace-form')
                </div>
            </div>
        </div>

        @if ($atlasNode->databaseProperties->isNotEmpty())
            <div class="mapping-client-props-compact">
                <span class="mapping-workspace-label">Client properties</span>
                <ul class="mapping-client-prop-chips">
                    @foreach ($atlasNode->databaseProperties as $clientProp)
                        <li class="mapping-client-prop-chip @if (in_array($clientProp->id, $mergedClientPropertyIds, true)) is-merged @endif">
                            <span>{{ $clientProp->name }}</span>
                            <span class="console-tag">{{ $clientProp->property_type }}</span>
                            @if (! in_array($clientProp->id, $mergedClientPropertyIds, true))
                                <form method="POST"
                                      action="{{ route('console.mappings.database.properties.merge', [$project, $atlasNode, $clientProp]) }}"
                                      class="mapping-client-prop-merge-form">
                                    @csrf
                                    <button type="submit" class="console-link-btn">+ schema</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

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
        <div class="mapping-workspace-top">
            <div class="mapping-workspace-fields">
                <div class="mapping-workspace-field">
                    <span class="mapping-workspace-label">Before</span>
                    <strong>{{ $atlasNode->label }}</strong>
                </div>
                <div class="mapping-workspace-field mapping-workspace-field-grow">
                    <span class="mapping-workspace-label">Canonical</span>
                    @include('console.mappings.partials.canonical-combobox', [
                        'node' => $atlasNode,
                        'mapping' => $mapping,
                        'canonicalDatabases' => $canonicalDatabases,
                    ])
                </div>
            </div>
        </div>

        @if ($atlasNode->databaseProperties->isNotEmpty())
            <p class="console-muted mapping-props-hint">{{ $atlasNode->databaseProperties->pluck('name')->join(', ') }}</p>
        @endif

        <p class="console-muted">Select or create a canonical target to begin building the migration schema.</p>
    @endif
</div>
