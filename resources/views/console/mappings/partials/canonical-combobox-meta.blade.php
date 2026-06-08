@php
    $selected = $mapping?->canonicalDatabase;
@endphp
<td class="mapping-col-meta">
    @if ($selected)
        @if ($selected->is_custom)
            <div class="mapping-meta-cell">
                <span class="console-tag console-tag-warn">Not in template</span>
            </div>
        @endif
        <div class="mapping-meta-cell mapping-meta-props">
            @if ($selected->properties->isNotEmpty())
                <span class="mapping-props-hint">{{ $selected->properties->pluck('name')->take(5)->join(', ') }}</span>
            @else
                <span class="console-muted">No properties defined</span>
            @endif
        </div>
    @else
        <span class="console-muted">—</span>
    @endif
</td>
