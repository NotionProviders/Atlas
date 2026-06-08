<div class="mapping-row-header">
    <h1 class="mapping-page-title">{{ $atlasNode->label }}</h1>

    <div class="mapping-row-header-canonical">
        @include('console.mappings.partials.canonical-combobox', [
            'node' => $atlasNode,
            'mapping' => $mapping,
            'canonicalDatabases' => $canonicalDatabases,
            'compact' => true,
            'inline' => true,
            'showMeta' => false,
        ])
    </div>

    @unless ($peekMode ?? false)
        <div class="mapping-page-actions">
            <a href="{{ route('console.mappings.database.peek-page', [$project, $atlasNode]) }}" class="console-btn console-btn-sm">Center peek</a>
            <a href="{{ route('console.mappings.index', $project) }}" class="console-btn console-btn-sm">Back</a>
        </div>
    @endunless
</div>

<div class="mapping-row-subheader">
    <div class="mapping-row-before">
        <span class="mapping-workspace-label">Before:</span>
        <strong>{{ $atlasNode->label }}</strong>
    </div>
    @if ($mapping)
        <div class="mapping-row-teamspace">
            <span class="mapping-workspace-label">Teamspace:</span>
            @include('console.mappings.partials.mapping-teamspace-form')
        </div>
    @endif
</div>
