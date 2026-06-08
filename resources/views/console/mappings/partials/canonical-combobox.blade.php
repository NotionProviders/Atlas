@php
    $selected = $mapping?->canonicalDatabase;
    $compact = $compact ?? false;
    $inline = $inline ?? false;
    $showMeta = $showMeta ?? true;
@endphp
<div class="canonical-combobox @if ($compact) canonical-combobox-compact @endif @if ($inline) canonical-combobox-inline @endif" data-node-id="{{ $node->id }}">
    @if ($inline)
        <span class="canonical-combobox-inline-label">Canonical:</span>
    @endif
    <div class="canonical-combobox-control">
        <input type="text"
               class="canonical-combobox-input"
               value="{{ $selected?->name ?? '' }}"
               placeholder="Search or type…"
               autocomplete="off"
               aria-label="Canonical database for {{ $node->label }}"
               data-selected-id="{{ $selected?->id }}">
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

    @if ($mapping)
        <form method="POST"
              action="{{ route('console.mappings.database.destroy', [$project, $node]) }}"
              class="canonical-combobox-unmap-form hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif

    <script type="application/json" class="canonical-combobox-options">@json($canonicalDatabases->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'is_custom' => $c->is_custom])->values())</script>

    @if ($showMeta && $selected)
        <div class="mapping-props-hint canonical-combobox-meta">
            @if ($selected->is_custom)
                <span class="console-tag console-tag-warn">Not in template</span>
            @endif
            {{ $selected->properties->pluck('name')->take(5)->join(', ') ?: 'No properties defined' }}
        </div>
    @endif
</div>
