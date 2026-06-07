<tr class="workspace-row @foreach ($ancestors as $ancestorId) workspace-under-{{ $ancestorId }} @endforeach"
    data-node-id="{{ $node->id }}"
    data-kind="{{ $node->kind->value }}"
    data-depth="{{ $depth }}">
    <td class="workspace-label-cell" data-col="name" style="padding-left: {{ ($depth * 1.25) + 0.75 }}rem">
        @if ($node->children->isNotEmpty())
            <button type="button" class="workspace-expand" aria-expanded="true" data-parent-id="{{ $node->id }}">▾</button>
        @else
            <span class="workspace-expand-spacer"></span>
        @endif
        @if ($node->kind->value === 'database')
            <button type="button"
                    class="workspace-db-btn"
                    data-node-id="{{ $node->id }}"
                    data-label="{{ $node->label }}"
                    data-props='@json($node->databaseProperties->map(fn ($p) => ["name" => $p->name, "type" => $p->property_type, "options" => $p->options])->values())'>
                {{ $node->label }}
            </button>
        @else
            <span>{{ $node->label }}</span>
        @endif
    </td>
    <td data-col="kind"><span class="workspace-kind workspace-kind-{{ $node->kind->value }}">{{ $node->kind->label() }}</span></td>
    <td data-col="notion_page_id">
        @if ($node->notion_page_id)
            <button type="button" class="workspace-copy" data-copy="{{ $node->notion_page_id }}">{{ $node->notion_page_id }}</button>
        @else
            <span class="console-muted">—</span>
        @endif
    </td>
    <td data-col="notion_data_source_id">
        @if ($node->notion_data_source_id)
            <button type="button" class="workspace-copy" data-copy="{{ $node->notion_data_source_id }}">{{ Str::limit($node->notion_data_source_id, 24) }}</button>
        @else
            <span class="console-muted">—</span>
        @endif
    </td>
    <td data-col="notes">{{ Str::limit($node->note, 80) }}</td>
</tr>
@php $childAncestors = array_merge($ancestors, [$node->id]); @endphp
@foreach ($node->children as $child)
    @include('console.snapshots.partials.table-row', ['node' => $child, 'depth' => $depth + 1, 'ancestors' => $childAncestors])
@endforeach
