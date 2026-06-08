<?php

namespace App\Http\Controllers\Console;

use App\Enums\NodeKind;
use App\Enums\SnapshotType;
use App\Http\Controllers\Console\CanonicalDatabasePropertyController;
use App\Http\Controllers\Controller;
use App\Models\AtlasNode;
use App\Models\CanonicalDatabase;
use App\Models\CanonicalPlacement;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CanonicalDatabaseController extends Controller
{
    public function index(): View
    {
        return $this->renderIndex(null);
    }

    public function indexWithPeek(CanonicalDatabase $canonicalDatabase): View
    {
        return $this->renderIndex($canonicalDatabase);
    }

    public function panel(Request $request, CanonicalDatabase $canonicalDatabase): View
    {
        return view('console.canonical.panel', $this->detailContext($request, $canonicalDatabase));
    }

    public function show(Request $request, CanonicalDatabase $canonicalDatabase): View
    {
        return view('console.canonical.show', $this->detailContext($request, $canonicalDatabase));
    }

    public function update(Request $request, CanonicalDatabase $canonicalDatabase): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string'],
            'project' => ['nullable', 'string', 'exists:projects,slug'],
        ]);

        $canonicalDatabase->update([
            'description' => $validated['description'] ?? null,
        ]);

        return $this->redirectBackToCanonical($request, $canonicalDatabase, 'Notes saved.');
    }

    public function updatePlacement(Request $request, CanonicalDatabase $canonicalDatabase): RedirectResponse
    {
        $validated = $request->validate([
            'project' => ['required', 'string', 'exists:projects,slug'],
            'teamspace_node_id' => ['nullable', 'exists:atlas_nodes,id'],
            'placement_notes' => ['nullable', 'string'],
        ]);

        $project = Project::query()->where('slug', $validated['project'])->firstOrFail();

        if ($validated['teamspace_node_id'] ?? null) {
            $this->assertTeamspaceInCanonSnapshot($project, (int) $validated['teamspace_node_id']);
        }

        $placement = CanonicalPlacement::query()->firstOrCreate([
            'project_id' => $project->id,
            'canonical_database_id' => $canonicalDatabase->id,
        ]);

        $placement->update([
            'teamspace_node_id' => $validated['teamspace_node_id'] ?? null,
            'placement_notes' => $validated['placement_notes'] ?? null,
        ]);

        return $this->redirectBackToCanonical($request, $canonicalDatabase, 'Canonical placement updated.', $project);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'properties' => ['nullable', 'array'],
            'properties.*.name' => ['required_with:properties', 'string', 'max:255'],
            'properties.*.property_type' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = Str::slug($validated['name']);
        $base = $slug;
        $i = 1;
        while (CanonicalDatabase::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        $canonical = CanonicalDatabase::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_custom' => (bool) $request->boolean('is_custom'),
            'sort_order' => (int) CanonicalDatabase::query()->max('sort_order') + 1,
        ]);

        foreach ($validated['properties'] ?? [] as $index => $property) {
            if (empty($property['name'])) {
                continue;
            }
            $canonical->properties()->create([
                'name' => $property['name'],
                'property_type' => $property['property_type'] ?? 'unknown',
                'sort_order' => $index,
            ]);
        }

        $redirect = $request->input('redirect');

        if ($redirect && str_starts_with($redirect, '/console')) {
            return redirect($redirect)->with('status', 'Canonical database "'.$canonical->name.'" added.');
        }

        return redirect()
            ->route('console.canonical.index')
            ->with('status', 'Canonical database created.');
    }

    public function destroy(CanonicalDatabase $canonicalDatabase): RedirectResponse
    {
        $name = $canonicalDatabase->name;
        $canonicalDatabase->delete();

        return back()->with('status', 'Removed "'.$name.'" from the canonical registry.');
    }

    /** @return array<string, mixed> */
    private function detailContext(Request $request, CanonicalDatabase $canonicalDatabase): array
    {
        $canonicalDatabase->load('properties');

        $mappingCount = $canonicalDatabase->databaseMappings()->count();
        $projectCount = (int) $canonicalDatabase->databaseMappings()
            ->distinct('project_id')
            ->count('project_id');

        $project = null;
        $placement = null;
        $projectMappings = collect();
        $teamspaces = collect();

        if ($projectSlug = $request->query('project')) {
            $project = Project::query()->where('slug', $projectSlug)->first();

            if ($project) {
                $projectMappings = $project->databaseMappings()
                    ->where('canonical_database_id', $canonicalDatabase->id)
                    ->with('atlasNode')
                    ->get();

                $placement = $project->canonicalPlacements()
                    ->where('canonical_database_id', $canonicalDatabase->id)
                    ->with('teamspaceNode')
                    ->first();

                if (! $placement && $projectMappings->isNotEmpty()) {
                    $placement = CanonicalPlacement::query()->create([
                        'project_id' => $project->id,
                        'canonical_database_id' => $canonicalDatabase->id,
                    ]);
                }

                $teamspaces = $this->canonTeamspaces($project);
            }
        }

        return compact(
            'canonicalDatabase',
            'mappingCount',
            'projectCount',
            'project',
            'placement',
            'projectMappings',
            'teamspaces',
        ) + [
            'propertyTypes' => CanonicalDatabasePropertyController::PROPERTY_TYPES,
        ];
    }

    private function redirectBackToCanonical(
        Request $request,
        CanonicalDatabase $canonicalDatabase,
        string $message,
        ?Project $project = null,
    ): RedirectResponse {
        $projectSlug = $project?->slug ?? $request->input('project') ?? $request->query('project');

        if ($projectSlug) {
            return redirect()
                ->route('console.canonical.show', [
                    'canonicalDatabase' => $canonicalDatabase,
                    'project' => $projectSlug,
                ])
                ->with('status', $message);
        }

        return redirect()
            ->route('console.canonical.show', $canonicalDatabase)
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

    private function renderIndex(?CanonicalDatabase $openPeek): View
    {
        $canonicalDatabases = CanonicalDatabase::query()
            ->with('properties')
            ->orderBy('is_lookup')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $entityDatabases = $canonicalDatabases->where('is_lookup', false)->values();
        $lookupDatabases = $canonicalDatabases->where('is_lookup', true)->values();

        return view('console.canonical.index', compact(
            'canonicalDatabases',
            'entityDatabases',
            'lookupDatabases',
            'openPeek',
        ));
    }
}
