@php
    $canonical = $row->canonical;
@endphp
<tr>
    <td>
        <span class="canonical-name-static">{{ $canonical->name }}</span>
    </td>
    <td>
        @if ($canonical->is_custom)
            <span class="console-tag console-tag-warn" title="Created for this project — not in the workspace template">Not in template</span>
        @else
            <span class="console-tag">Template</span>
        @endif
    </td>
    <td>
        <span class="mapping-client-list">
            @foreach ($row->mappings as $mapping)
                <a href="{{ route('console.mappings.database.show', [$project, $mapping->atlasNode]) }}" class="canonical-name-link">{{ $mapping->atlasNode->label }}</a>@if (! $loop->last), @endif
            @endforeach
        </span>
    </td>
    <td class="mapping-col-actions">
        <div class="mapping-row-actions">
            @include('console.mappings.partials.canonical-template-btn', ['canonical' => $canonical])
        </div>
    </td>
</tr>
