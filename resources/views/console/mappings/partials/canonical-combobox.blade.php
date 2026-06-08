@php
    $selected = $mapping?->canonicalDatabase;
    $selectedName = $selected?->name ?? '';
    $selectedId = $selected?->id;
@endphp
<div class="canonical-combobox" data-node-id="{{ $node->id }}">
    <div class="canonical-combobox-control">
        <input type="text"
               class="canonical-combobox-input"
               value="{{ $selectedName }}"
               placeholder="Search or type to create…"
               autocomplete="off"
               aria-label="Canonical database for {{ $node->label }}"
               data-selected-id="{{ $selectedId }}">
        <ul class="canonical-combobox-list hidden" role="listbox"></ul>
    </div>

    <form method="POST"
          action="{{ route('console.mappings.store', $project) }}"
          class="canonical-combobox-map-form hidden">
        @csrf
        <input type="hidden" name="atlas_node_id" value="{{ $node->id }}">
        <input type="hidden" name="canonical_database_id" value="">
    </form>

    <form method="POST"
          action="{{ route('console.mappings.quick-canonical', $project) }}"
          class="canonical-combobox-create-form hidden">
        @csrf
        <input type="hidden" name="atlas_node_id" value="{{ $node->id }}">
        <input type="hidden" name="name" value="">
    </form>

    <script type="application/json" class="canonical-combobox-options">@json($canonicalDatabases->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'is_custom' => $c->is_custom])->values())</script>

    @if ($selected)
        <div class="mapping-props-hint">
            @if ($selected->is_custom)
                <span class="console-tag console-tag-warn">Not in template</span>
            @endif
            {{ $selected->properties->pluck('name')->take(5)->join(', ') ?: 'No properties defined' }}
        </div>
    @endif
</div>
