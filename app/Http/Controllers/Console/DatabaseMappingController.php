<?php

namespace App\Http\Controllers\Console;

use App\Enums\NodeKind;
use App\Enums\SnapshotType;
use App\Http\Controllers\Controller;
use App\Models\AtlasNode;
use App\Models\CanonicalDatabase;
use App\Models\DatabaseMapping;
use App\Models\Project;
use App\Services\MappingPropertySyncService;
use App\Support\ProjectCanonicalTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DatabaseMappingController extends Controller
{
    public function __construct(
        private readonly MappingPropertySyncService $propertySync,
    ) {}

    public function index(Project $project): View
    {
        return $this->renderIndex($project, null);
    }

    public function indexWithDatabasePeek(Project $project, AtlasNode $atlasNode): View
    {
        $this->assertDatabaseNodeInBeforeSnapshot($project, $atlasNode);

        return $this->renderIndex($project, $atlasNode);
    }

    public function databasePanel(Project $project, AtlasNode $atlasNode): View
    {
        return view('console.mappings.database-panel', $this->databaseContext($project, $atlasNode));
    }

    public function databaseShow(Project $project, AtlasNode $atlasNode): View
    {
        return view('console.mappings.database-show', $this->databaseContext($project, $atlasNode));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'atlas_node_id' => ['required', 'exists:atlas_nodes,id'],
            'canonical_database_id' => ['required', 'exists:canonical_databases,id'],
            'notes' => ['nullable', 'string'],
            'migration_details' => ['nullable', 'string'],
        ]);

        $node = AtlasNode::query()->findOrFail($validated['atlas_node_id']);
        abort_unless($node->kind === NodeKind::Database, 422);
        $this->assertDatabaseNodeInBeforeSnapshot($project, $node);

        $hadMappings = $project->databaseMappings()->exists();

        $mapping = DatabaseMapping::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'atlas_node_id' => $validated['atlas_node_id'],
            ],
            [
                'canonical_database_id' => $validated['canonical_database_id'],
                'notes' => $validated['notes'] ?? null,
                'migration_details' => $validated['migration_details'] ?? null,
            ],
        );

        if ($mapping->wasRecentlyCreated || $mapping->wasChanged('canonical_database_id')) {
            $this->propertySync->syncFromCanonical($mapping);
        }

        return $this->afterMappingSaved($project, $hadMappings, 'Mapping saved.');
    }

    public function quickCanonical(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'atlas_node_id' => ['required', 'exists:atlas_nodes,id'],
        ]);

        $node = AtlasNode::query()->findOrFail($validated['atlas_node_id']);
        abort_unless($node->kind === NodeKind::Database, 422);
        $this->assertDatabaseNodeInBeforeSnapshot($project, $node);

        $hadMappings = $project->databaseMappings()->exists();

        $name = trim($validated['name']);
        $canonical = CanonicalDatabase::query()->where('name', $name)->first();

        if (! $canonical) {
            $slug = Str::slug($name);
            $base = $slug;
            $i = 1;
            while (CanonicalDatabase::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }

            $canonical = CanonicalDatabase::query()->create([
                'name' => $name,
                'slug' => $slug,
                'is_custom' => true,
                'is_lookup' => false,
                'sort_order' => (int) CanonicalDatabase::query()->max('sort_order') + 1,
            ]);
        }

        $mapping = DatabaseMapping::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'atlas_node_id' => $validated['atlas_node_id'],
            ],
            [
                'canonical_database_id' => $canonical->id,
            ],
        );

        if ($mapping->wasRecentlyCreated || $mapping->wasChanged('canonical_database_id')) {
            $this->propertySync->syncFromCanonical($mapping);
        }

        $message = 'Mapped to "'.$canonical->name.'".'.($canonical->wasRecentlyCreated ? ' New canonical database added to this project.' : '');

        return $this->afterMappingSaved($project, $hadMappings, $message);
    }

    public function updateDatabaseMapping(Request $request, Project $project, AtlasNode $atlasNode): RedirectResponse
    {
        $this->assertDatabaseNodeInBeforeSnapshot($project, $atlasNode);

        $mapping = $project->databaseMappings()
            ->where('atlas_node_id', $atlasNode->id)
            ->firstOrFail();

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'migration_details' => ['nullable', 'string'],
            'teamspace_node_id' => ['nullable', 'exists:atlas_nodes,id'],
            'placement_notes' => ['nullable', 'string'],
        ]);

        if ($validated['teamspace_node_id'] ?? null) {
            $this->assertTeamspaceInCanonSnapshot($project, (int) $validated['teamspace_node_id']);
        }

        $mapping->update($validated);

        return back()->with('status', 'Migration details saved.');
    }

    public function destroyDatabaseMapping(Project $project, AtlasNode $atlasNode): RedirectResponse
    {
        $this->assertDatabaseNodeInBeforeSnapshot($project, $atlasNode);

        $mapping = $project->databaseMappings()
            ->where('atlas_node_id', $atlasNode->id)
            ->firstOrFail();

        $mapping->delete();

        return redirect()
            ->route('console.mappings.database.show', [$project, $atlasNode])
            ->with('status', 'Mapping removed.');
    }

    private function renderIndex(Project $project, ?AtlasNode $openDatabaseNode): View
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
            ->with(['canonicalDatabase', 'atlasNode', 'teamspaceNode'])
            ->get()
            ->keyBy('atlas_node_id');

        $canonicalDatabases = CanonicalDatabase::query()
            ->with('properties')
            ->orderBy('is_custom')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $projectCanonicalRows = ProjectCanonicalTable::rows($project);

        return view('console.mappings.index', compact(
            'project',
            'beforeSnapshot',
            'clientDatabases',
            'mappingsByNodeId',
            'canonicalDatabases',
            'projectCanonicalRows',
            'openDatabaseNode',
        ));
    }

    /** @return array<string, mixed> */
    private function databaseContext(Project $project, AtlasNode $atlasNode): array
    {
        $this->assertDatabaseNodeInBeforeSnapshot($project, $atlasNode);

        $atlasNode->load('databaseProperties');

        $mapping = $project->databaseMappings()
            ->where('atlas_node_id', $atlasNode->id)
            ->with(['canonicalDatabase', 'properties', 'teamspaceNode'])
            ->first();

        $canonicalDatabases = CanonicalDatabase::query()
            ->with('properties')
            ->orderBy('is_custom')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $teamspaces = $this->canonTeamspaces($project);

        $mergedClientPropertyIds = $mapping
            ? $mapping->properties->pluck('source_client_property_id')->filter()->all()
            : [];

        return compact(
            'project',
            'atlasNode',
            'mapping',
            'canonicalDatabases',
            'teamspaces',
            'mergedClientPropertyIds',
        ) + [
            'propertyTypes' => CanonicalDatabasePropertyController::PROPERTY_TYPES,
        ];
    }

    private function afterMappingSaved(Project $project, bool $hadMappings, string $message): RedirectResponse
    {
        if ($hadMappings) {
            return back()->with('status', $message);
        }

        return redirect()
            ->route('console.snapshots.show', [$project, 'canon', 'view' => 'table'])
            ->with('status', $message);
    }

    /** @return \Illuminate\Support\Collection<int, AtlasNode> */
    private function canonTeamspaces(Project $project)
    {
        $canonSnapshot = $project->snapshots()->where('type', SnapshotType::Canon)->first();

        if (! $canonSnapshot || $canonSnapshot->isEmpty()) {
            return collect();
        }

        return AtlasNode::query()
            ->where('snapshot_id', $canonSnapshot->id)
            ->where('kind', NodeKind::Teamspace)
            ->orderBy('label')
            ->get();
    }

    private function assertTeamspaceInCanonSnapshot(Project $project, int $teamspaceNodeId): void
    {
        $canonSnapshot = $project->snapshots()->where('type', SnapshotType::Canon)->first();
        abort_unless($canonSnapshot && ! $canonSnapshot->isEmpty(), 422);

        $valid = AtlasNode::query()
            ->where('id', $teamspaceNodeId)
            ->where('snapshot_id', $canonSnapshot->id)
            ->where('kind', NodeKind::Teamspace)
            ->exists();

        abort_unless($valid, 422);
    }

    private function assertDatabaseNodeInBeforeSnapshot(Project $project, AtlasNode $atlasNode): void
    {
        abort_unless($atlasNode->kind === NodeKind::Database, 404);

        $beforeSnapshot = $project->snapshots()->where('type', SnapshotType::Before)->first();
        abort_unless($beforeSnapshot && ! $beforeSnapshot->isEmpty(), 404);
        abort_unless($atlasNode->snapshot_id === $beforeSnapshot->id, 404);
    }
}
