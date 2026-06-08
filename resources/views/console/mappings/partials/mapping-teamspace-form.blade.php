<section class="canonical-details-section">
    <h3>Teamspace</h3>
    <p class="console-muted">Where this database will live in the Canonical workspace for this client.</p>
    <form method="POST" action="{{ route('console.mappings.database.update', [$project, $atlasNode]) }}" class="console-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="notes" value="{{ $mapping->notes }}">
        <input type="hidden" name="migration_details" value="{{ $mapping->migration_details }}">
        <label>
            <span>Teamspace</span>
            @if ($teamspaces->isEmpty())
                <select name="teamspace_node_id" disabled>
                    <option>Import a Canonical snapshot with teamspaces first</option>
                </select>
                <span class="console-muted">Canonical snapshot not imported yet — teamspace can be set later.</span>
            @else
                <select name="teamspace_node_id">
                    <option value="">Pending teamspace…</option>
                    @foreach ($teamspaces as $teamspace)
                        <option value="{{ $teamspace->id }}" @selected($mapping->teamspace_node_id === $teamspace->id)>
                            {{ $teamspace->label }}
                        </option>
                    @endforeach
                </select>
            @endif
        </label>
        <label>
            <span>Placement notes</span>
            <textarea name="placement_notes" rows="3" placeholder="Where this database lives in the target workspace…">{{ old('placement_notes', $mapping->placement_notes) }}</textarea>
        </label>
        @if ($teamspaces->isNotEmpty())
            <button type="submit" class="console-btn console-btn-primary">Save teamspace</button>
        @endif
    </form>
</section>
