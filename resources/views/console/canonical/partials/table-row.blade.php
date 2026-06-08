@php
    $propertyPreview = $canonical->properties->take(4)->pluck('name')->join(', ');
    $propertyOverflow = max(0, $canonical->properties->count() - 4);
@endphp
<tr>
    <td>
        <a href="{{ route('console.canonical.show', $canonical) }}" class="canonical-name-link">{{ $canonical->name }}</a>
    </td>
    <td>
        @if ($canonical->is_lookup)
            <span class="console-pill">Lookup</span>
        @else
            <span class="console-pill console-pill-ok">Entity</span>
        @endif
    </td>
    <td class="canonical-col-count">{{ $canonical->properties->count() }}</td>
    <td class="canonical-col-info">
        <button type="button"
                class="canonical-info-btn"
                data-slug="{{ $canonical->slug }}"
                data-panel-url="{{ route('console.canonical.panel', $canonical) }}"
                data-peek-url="{{ route('console.canonical.peek-page', $canonical) }}"
                aria-label="View details for {{ $canonical->name }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M12 8v4M12 16h.01"/>
            </svg>
            <span class="canonical-info-tooltip" role="tooltip">
                <strong>{{ $canonical->name }}</strong>
                <span>{{ $canonical->is_lookup ? 'Lookup / taxonomy database' : 'Entity database' }}</span>
                @if ($canonical->properties->isNotEmpty())
                    <span>
                        {{ $canonical->properties->count() }} properties
                        @if ($propertyPreview)
                            : {{ $propertyPreview }}
                            @if ($propertyOverflow > 0)
                                +{{ $propertyOverflow }} more
                            @endif
                        @endif
                    </span>
                @else
                    <span>No properties defined</span>
                @endif
                <span class="canonical-info-hint">Click for full details</span>
            </span>
        </button>
    </td>
</tr>
