@php
    $canonical = $row->canonical;
    $placement = $row->placement;
    $inTeamspace = $placement && $placement->isAssignedToTeamspace();
    $canonicalParams = ['canonicalDatabase' => $canonical, 'project' => $project->slug];
@endphp
<tr>
    <td>
        <a href="{{ route('console.canonical.show', $canonicalParams) }}" class="canonical-name-link">{{ $canonical->name }}</a>
    </td>
    <td>
        @if ($canonical->is_custom)
            <span class="console-tag console-tag-warn" title="Created for this project — not in the workspace template">Not in template</span>
        @elseif ($canonical->notion_export_id)
            <span class="console-tag">Template</span>
        @else
            <span class="console-tag">Manual</span>
        @endif
    </td>
    <td>
        <span class="mapping-client-list">
            @foreach ($row->mappings as $mapping)
                <a href="{{ route('console.mappings.database.show', [$project, $mapping->atlasNode]) }}" class="canonical-name-link">{{ $mapping->atlasNode->label }}</a>@if (! $loop->last), @endif
            @endforeach
        </span>
    </td>
    <td>
        @if ($inTeamspace)
            <span class="console-tag console-tag-ok">{{ $placement->teamspaceNode->label }}</span>
        @else
            <span class="console-tag console-tag-warn">Pending teamspace</span>
        @endif
    </td>
    <td class="canonical-col-info">
        <button type="button"
                class="canonical-info-btn"
                data-panel-url="{{ route('console.canonical.panel', $canonicalParams) }}"
                data-peek-url="{{ route('console.mappings.canonical.peek-page', [$project, $canonical]) }}"
                aria-label="View details for {{ $canonical->name }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M12 8v4M12 16h.01"/>
            </svg>
            <span class="canonical-info-tooltip" role="tooltip">
                <strong>{{ $canonical->name }}</strong>
                @if ($canonical->is_custom)
                    <span>Not in workspace template</span>
                @endif
                <span>{{ $row->mappings->count() }} client {{ Str::plural('mapping', $row->mappings->count()) }}</span>
                <span>{{ $inTeamspace ? 'Teamspace: '.$placement->teamspaceNode->label : 'Pending teamspace assignment' }}</span>
                <span class="canonical-info-hint">Click for placement details</span>
            </span>
        </button>
    </td>
</tr>
