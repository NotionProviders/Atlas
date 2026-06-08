<div class="canonical-details">
    <dl class="canonical-details-meta">
        <div>
            <dt>Kind</dt>
            <dd>
                @if ($canonicalDatabase->is_lookup)
                    Lookup / taxonomy
                @else
                    Entity
                @endif
            </dd>
        </div>
        @if ($canonicalDatabase->notion_export_id)
            <div>
                <dt>Notion export ID</dt>
                <dd><code class="console-type-code">{{ $canonicalDatabase->notion_export_id }}</code></dd>
            </div>
        @endif
        <div>
            <dt>Project mappings</dt>
            <dd>{{ $mappingCount }} mapping{{ $mappingCount === 1 ? '' : 's' }} across {{ $projectCount }} project{{ $projectCount === 1 ? '' : 's' }}</dd>
        </div>
    </dl>

    @if ($canonicalDatabase->description)
        <section class="canonical-details-section">
            <h3>About</h3>
            <p class="console-muted canonical-details-about">{{ $canonicalDatabase->description }}</p>
        </section>
    @endif

    <section class="canonical-details-section">
        <h3>How this database is used</h3>
        @if ($canonicalDatabase->is_lookup)
            <p class="console-muted">
                Lookup and taxonomy databases hold shared reference values — statuses, types, categories, and other
                pick-list data that entity databases relate to. Map client taxonomy databases here when they serve
                the same reference role in the target workspace.
            </p>
        @else
            <p class="console-muted">
                Entity databases store primary business records — companies, people, projects, assets, and similar
                objects clients work with day to day. Map each client database in a Before snapshot to the canonical
                entity that best matches its role, even when several client databases share one target.
            </p>
        @endif
    </section>

    <section class="canonical-details-section">
        <h3>Properties ({{ $canonicalDatabase->properties->count() }})</h3>
        @if ($canonicalDatabase->properties->isEmpty())
            <p class="console-muted">No properties defined.</p>
        @else
            <table class="workspace-props-table canonical-props-table">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Type</th>
                        <th>Title</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($canonicalDatabase->properties as $prop)
                        <tr>
                            <td>{{ $prop->name }}</td>
                            <td><code class="console-type-code">{{ $prop->property_type }}</code></td>
                            <td>
                                @if ($prop->is_title)
                                    <span class="console-pill console-pill-ok">Title</span>
                                @else
                                    <span class="console-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <form method="POST"
          action="{{ route('console.canonical.destroy', $canonicalDatabase) }}"
          class="canonical-details-danger"
          onsubmit="return confirm('Remove this canonical database? Existing project mappings will be deleted.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="console-link-btn console-link-danger">Remove from registry</button>
    </form>
</div>
