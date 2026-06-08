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
