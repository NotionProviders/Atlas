<form method="POST" action="{{ route('console.mappings.database.update', [$project, $atlasNode]) }}" class="mapping-teamspace-inline">
    @csrf
    @method('PUT')
    <input type="hidden" name="notes" value="{{ $mapping->notes }}">
    <input type="hidden" name="migration_details" value="{{ $mapping->migration_details }}">
    <input type="hidden" name="placement_notes" value="{{ $mapping->placement_notes }}">
    @if ($projectTeamspaces->isEmpty())
        <span class="console-muted">Add a teamspace below first</span>
    @else
        <select name="project_teamspace_id" class="mapping-teamspace-select" onchange="this.form.submit()">
            <option value="">Unassigned…</option>
            @foreach ($projectTeamspaces as $teamspace)
                <option value="{{ $teamspace->id }}" @selected($mapping->project_teamspace_id === $teamspace->id)>
                    {{ $teamspace->name }}
                </option>
            @endforeach
        </select>
    @endif
</form>
