@extends('console.layouts.app')

@section('title', 'Database mappings — '.$project->name)

@section('content')
<div class="console-page-header">
    <div>
        <h1>Database mappings</h1>
        <p class="console-muted">Map each client database (Before) to one canonical target. Many client databases can share the same canonical database.</p>
    </div>
    <div class="console-page-actions">
        <a href="{{ route('console.canonical.index') }}" class="console-btn">Canonical registry</a>
        <a href="{{ route('console.projects.show', $project) }}" class="console-btn">Back to project</a>
    </div>
</div>

@if (!$beforeSnapshot || $beforeSnapshot->isEmpty())
    <p class="console-muted">Import a <strong>Before</strong> snapshot first to see client databases here.</p>
@else
    <details class="console-add-canonical">
        <summary class="console-btn">+ Add canonical database</summary>
        <form method="POST" action="{{ route('console.canonical.store') }}" class="console-form console-add-canonical-form">
            @csrf
            <input type="hidden" name="redirect" value="{{ request()->getRequestUri() }}">
            <label>
                <span>Name</span>
                <input type="text" name="name" required placeholder="e.g. Companies">
            </label>
            <label>
                <span>Description</span>
                <textarea name="description" rows="2" placeholder="What this canonical database represents"></textarea>
            </label>
            <fieldset class="console-property-fieldset">
                <legend>Standard properties (optional)</legend>
                @for ($i = 0; $i < 3; $i++)
                    <div class="console-property-row">
                        <input type="text" name="properties[{{ $i }}][name]" placeholder="Property name">
                        <input type="text" name="properties[{{ $i }}][property_type]" placeholder="Type (e.g. select)">
                    </div>
                @endfor
            </fieldset>
            <button type="submit" class="console-btn console-btn-primary">Add to registry</button>
        </form>
    </details>

    @if ($clientDatabases->isEmpty())
        <p class="console-muted">No databases found in the Before snapshot.</p>
    @elseif ($canonicalDatabases->isEmpty())
        <p class="console-muted">Add canonical databases above (or in the <a href="{{ route('console.canonical.index') }}">registry</a>) before mapping.</p>
    @else
        <table class="workspace-table mapping-table">
            <thead>
                <tr>
                    <th class="mapping-col-client">Client database (Before)</th>
                    <th class="mapping-col-arrow"></th>
                    <th class="mapping-col-canonical">Maps to (Canonical)</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clientDatabases as $node)
                    @php $mapping = $mappingsByNodeId->get($node->id); @endphp
                    <tr>
                        <td class="mapping-col-client">
                            <strong>{{ $node->label }}</strong>
                            @if ($node->databaseProperties->isNotEmpty())
                                <div class="mapping-props-hint">
                                    {{ $node->databaseProperties->pluck('name')->join(', ') }}
                                </div>
                            @endif
                        </td>
                        <td class="mapping-col-arrow">→</td>
                        <td class="mapping-col-canonical">
                            <form method="POST" action="{{ route('console.mappings.store', $project) }}" class="mapping-row-form">
                                @csrf
                                <input type="hidden" name="atlas_node_id" value="{{ $node->id }}">
                                <select name="canonical_database_id" required onchange="this.form.submit()">
                                    <option value="">Select canonical…</option>
                                    @foreach ($canonicalDatabases as $canonical)
                                        <option value="{{ $canonical->id }}" @selected($mapping?->canonical_database_id === $canonical->id)>
                                            {{ $canonical->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                            @if ($mapping?->canonicalDatabase)
                                <div class="mapping-props-hint">
                                    {{ $mapping->canonicalDatabase->properties->pluck('name')->join(', ') ?: 'No standard properties defined' }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if ($mapping)
                                <form method="POST" action="{{ route('console.mappings.store', $project) }}" class="mapping-notes-form">
                                    @csrf
                                    <input type="hidden" name="atlas_node_id" value="{{ $node->id }}">
                                    <input type="hidden" name="canonical_database_id" value="{{ $mapping->canonical_database_id }}">
                                    <textarea name="notes" rows="2" placeholder="Migration notes…">{{ $mapping->notes }}</textarea>
                                    <button type="submit" class="console-link-btn">Save notes</button>
                                </form>
                            @else
                                <span class="console-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($mapping)
                                <form method="POST" action="{{ route('console.mappings.destroy', [$project, $mapping]) }}" onsubmit="return confirm('Remove this mapping?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="console-link-btn">Clear</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif
@endsection
