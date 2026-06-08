@php
    $selected = $mapping->projectTeamspace ?? null;
@endphp
<div class="teamspace-combobox canonical-combobox-compact" data-node-id="{{ $atlasNode->id }}">
    <div class="teamspace-combobox-control canonical-combobox-control">
        <input type="text"
               class="teamspace-combobox-input canonical-combobox-input"
               value="{{ $selected?->name ?? '' }}"
               placeholder="Search or type…"
               autocomplete="off"
               aria-label="Teamspace for {{ $atlasNode->label }}"
               data-selected-id="{{ $selected?->id }}">
        <ul class="teamspace-combobox-list canonical-combobox-list hidden" role="listbox"></ul>
    </div>

    <form method="POST"
          action="{{ route('console.mappings.database.update', [$project, $atlasNode]) }}"
          class="teamspace-combobox-assign-form hidden">
        @csrf
        @method('PUT')
        <input type="hidden" name="notes" value="{{ $mapping->notes }}">
        <input type="hidden" name="migration_details" value="{{ $mapping->migration_details }}">
        <input type="hidden" name="placement_notes" value="{{ $mapping->placement_notes }}">
        <input type="hidden" name="project_teamspace_id" value="">
    </form>

    <form method="POST"
          action="{{ route('console.teamspaces.store', $project) }}"
          class="teamspace-combobox-create-form hidden">
        @csrf
        <input type="hidden" name="assign_atlas_node_id" value="{{ $atlasNode->id }}">
        <input type="hidden" name="name" value="">
    </form>

    <script type="application/json" class="teamspace-combobox-options">@json($projectTeamspaces->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values())</script>
</div>
