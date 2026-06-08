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
                @if ($mapping)
                    <p class="console-muted mapping-canonical-ref">
                        Template reference:
                        <a href="{{ route('console.canonical.show', $mapping->canonicalDatabase) }}" class="canonical-name-link" target="_blank" rel="noopener">{{ $mapping->canonicalDatabase->name }}</a>
                    </p>
                @endif
            </dd>
        </div>
    </dl>

    @if ($mapping)
        @include('console.mappings.partials.mapping-teamspace-form')

        @if ($atlasNode->databaseProperties->isNotEmpty())
            <section class="canonical-details-section">
                <h3>Client properties</h3>
                <p class="console-muted">Merge properties from the Before database into the migration schema below.</p>
                <ul class="mapping-client-prop-list">
                    @foreach ($atlasNode->databaseProperties as $clientProp)
                        <li class="mapping-client-prop-item">
                            <span>{{ $clientProp->name }}</span>
                            <span class="console-tag">{{ $clientProp->property_type }}</span>
                            @if (in_array($clientProp->id, $mergedClientPropertyIds, true))
                                <span class="console-tag console-tag-ok">In schema</span>
                            @else
                                <form method="POST"
                                      action="{{ route('console.mappings.database.properties.merge', [$project, $atlasNode, $clientProp]) }}"
                                      class="mapping-client-prop-merge-form">
                                    @csrf
                                    <button type="submit" class="console-btn">Add to schema</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @include('console.mappings.partials.mapping-properties-editor')

        <section class="canonical-details-section">
            <h3>Migration notes</h3>
            <form method="POST" action="{{ route('console.mappings.database.update', [$project, $atlasNode]) }}" class="console-form">
                @csrf
                @method('PUT')
                <label>
                    <span class="console-muted">Short notes</span>
                    <input type="text" name="notes" value="{{ old('notes', $mapping->notes) }}" placeholder="One-line summary">
                </label>
                <label>
                    <span class="console-muted">Migration plan</span>
                    <textarea name="migration_details" rows="6" placeholder="Data cleanup, merge strategy, exceptions…">{{ old('migration_details', $mapping->migration_details) }}</textarea>
                </label>
                <input type="hidden" name="teamspace_node_id" value="{{ $mapping->teamspace_node_id }}">
                <input type="hidden" name="placement_notes" value="{{ $mapping->placement_notes }}">
                <button type="submit" class="console-btn console-btn-primary">Save notes</button>
            </form>
        </section>

        <form method="POST"
              action="{{ route('console.mappings.database.destroy', [$project, $atlasNode]) }}"
              class="canonical-details-danger"
              onsubmit="return confirm('Remove this mapping?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="console-link-btn console-link-danger">Remove mapping</button>
        </form>
    @else
        @if ($atlasNode->databaseProperties->isNotEmpty())
            <section class="canonical-details-section">
                <h3>Client properties</h3>
                <p class="console-muted mapping-props-hint">{{ $atlasNode->databaseProperties->pluck('name')->join(', ') }}</p>
            </section>
        @endif

        <section class="canonical-details-section">
            <p class="console-muted">Select or create a canonical target above to begin building the migration schema.</p>
        </section>
    @endif
</div>
