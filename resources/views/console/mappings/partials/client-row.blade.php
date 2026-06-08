<tr>
    <td class="mapping-col-client">
        <strong>{{ $node->label }}</strong>
        @if ($node->databaseProperties->isNotEmpty())
            <div class="mapping-props-hint">
                {{ $node->databaseProperties->pluck('name')->take(6)->join(', ') }}
            </div>
        @endif
    </td>
    <td class="mapping-col-arrow">→</td>
    <td class="mapping-col-canonical">
        @include('console.mappings.partials.canonical-combobox', [
            'node' => $node,
            'mapping' => $mapping,
            'canonicalDatabases' => $canonicalDatabases,
        ])
    </td>
    <td class="canonical-col-info">
        <button type="button"
                class="canonical-info-btn"
                data-panel-url="{{ route('console.mappings.database.panel', [$project, $node]) }}"
                data-peek-url="{{ route('console.mappings.database.peek-page', [$project, $node]) }}"
                aria-label="View migration details for {{ $node->label }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M12 8v4M12 16h.01"/>
            </svg>
            <span class="canonical-info-tooltip" role="tooltip">
                <strong>{{ $node->label }}</strong>
                @if ($mapping)
                    <span>→ {{ $mapping->canonicalDatabase->name }}</span>
                    @if ($mapping->notes)
                        <span>{{ Str::limit($mapping->notes, 80) }}</span>
                    @endif
                @else
                    <span>Not mapped yet</span>
                @endif
                <span class="canonical-info-hint">Click for migration details</span>
            </span>
        </button>
    </td>
</tr>
