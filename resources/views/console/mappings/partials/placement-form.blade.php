<section class="canonical-details-section">
    <h3>Teamspace placement</h3>
    <p class="console-muted">When assigned, this database will appear in the Canonical table view under the selected teamspace.</p>
    <form method="POST" action="{{ route('console.canonical.placement.update', $canonicalDatabase) }}" class="console-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="project" value="{{ $project->slug }}">
        <label>
            <span>Teamspace</span>
            @if ($teamspaces->isEmpty())
                <select name="teamspace_node_id" disabled>
                    <option>Import a Canonical snapshot with teamspaces first</option>
                </select>
                <span class="console-muted">Canonical snapshot not imported yet — placement can be set later.</span>
            @else
                <select name="teamspace_node_id">
                    <option value="">Pending teamspace…</option>
                    @foreach ($teamspaces as $teamspace)
                        <option value="{{ $teamspace->id }}" @selected($placement?->teamspace_node_id === $teamspace->id)>
                            {{ $teamspace->label }}
                        </option>
                    @endforeach
                </select>
            @endif
        </label>
        <label>
            <span>Placement notes</span>
            <textarea name="placement_notes" rows="3" placeholder="Where this database lives in the target workspace…">{{ old('placement_notes', $placement?->placement_notes) }}</textarea>
        </label>
        @if ($teamspaces->isNotEmpty())
            <button type="submit" class="console-btn console-btn-primary">Save placement</button>
        @endif
    </form>
</section>
