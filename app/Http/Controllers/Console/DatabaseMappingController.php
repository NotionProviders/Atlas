<?php

namespace App\Http\Controllers\Console;

use App\Enums\NodeKind;
use App\Enums\SnapshotType;
use App\Http\Controllers\Controller;
use App\Models\AtlasNode;
use App\Models\CanonicalDatabase;
use App\Models\DatabaseMapping;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DatabaseMappingController extends Controller
{
    public function index(Project $project): View
    {
        $beforeSnapshot = $project->snapshots()->where('type', SnapshotType::Before)->first();

        $clientDatabases = $beforeSnapshot && ! $beforeSnapshot->isEmpty()
            ? AtlasNode::query()
                ->where('snapshot_id', $beforeSnapshot->id)
                ->where('kind', NodeKind::Database)
                ->with('databaseProperties')
                ->orderBy('label')
                ->get()
            : collect();

        $mappingsByNodeId = $project->databaseMappings()
            ->with('canonicalDatabase.properties')
            ->get()
            ->keyBy('atlas_node_id');

        $canonicalDatabases = CanonicalDatabase::query()
            ->with('properties')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('console.mappings.index', compact(
            'project',
            'beforeSnapshot',
            'clientDatabases',
            'mappingsByNodeId',
            'canonicalDatabases',
        ));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'atlas_node_id' => ['required', 'exists:atlas_nodes,id'],
            'canonical_database_id' => ['required', 'exists:canonical_databases,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $node = AtlasNode::query()->findOrFail($validated['atlas_node_id']);
        abort_unless($node->kind === NodeKind::Database, 422);

        DatabaseMapping::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'atlas_node_id' => $validated['atlas_node_id'],
            ],
            [
                'canonical_database_id' => $validated['canonical_database_id'],
                'notes' => $validated['notes'] ?? null,
            ],
        );

        return back()->with('status', 'Mapping saved.');
    }

    public function destroy(Project $project, DatabaseMapping $mapping): RedirectResponse
    {
        abort_unless($mapping->project_id === $project->id, 404);

        $mapping->delete();

        return back()->with('status', 'Mapping removed.');
    }
}
