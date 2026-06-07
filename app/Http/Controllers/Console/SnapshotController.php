<?php

namespace App\Http\Controllers\Console;

use App\Enums\SnapshotType;
use App\Http\Controllers\Controller;
use App\Models\AtlasNode;
use App\Models\Project;
use App\Models\Snapshot;
use App\Services\AtlasTreeBuilder;
use App\Services\SnapshotImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SnapshotController extends Controller
{
    public function __construct(
        private readonly AtlasTreeBuilder $treeBuilder,
        private readonly SnapshotImporter $importer,
    ) {}

    public function show(Request $request, Project $project, string $type): View|RedirectResponse
    {
        $snapshotType = SnapshotType::from($type);
        $snapshot = $this->resolveSnapshot($project, $snapshotType);
        $view = $request->query('view', 'atlas') === 'table' ? 'table' : 'atlas';

        if ($snapshot->isEmpty()) {
            return view('console.snapshots.empty', compact('project', 'snapshot', 'snapshotType', 'view'));
        }

        if ($view === 'table') {
            $teamspaces = $snapshot->nodes()
                ->where('kind', 'teamspace')
                ->with([
                    'databaseProperties',
                    'children.databaseProperties',
                    'children.children.databaseProperties',
                    'children.children.children.databaseProperties',
                ])
                ->orderBy('sort_order')
                ->get();

            return view('console.snapshots.show-table', compact('project', 'snapshot', 'snapshotType', 'teamspaces'));
        }

        try {
            $atlasConfigScript = $this->treeBuilder->buildConfigScript($snapshot);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('console.projects.show', $project)
                ->withErrors(['snapshot' => $e->getMessage()]);
        }

        return view('console.snapshots.show-atlas', compact(
            'project',
            'snapshot',
            'snapshotType',
            'atlasConfigScript',
        ));
    }

    public function embed(Project $project, string $type): View|RedirectResponse
    {
        $snapshotType = SnapshotType::from($type);
        $snapshot = $this->resolveSnapshot($project, $snapshotType);

        if ($snapshot->isEmpty()) {
            return view('console.snapshots.embed-empty', compact('project', 'snapshotType'));
        }

        try {
            $atlasConfigScript = $this->treeBuilder->buildConfigScript($snapshot);
        } catch (RuntimeException $e) {
            return view('console.snapshots.embed-empty', [
                'project' => $project,
                'snapshotType' => $snapshotType,
                'message' => $e->getMessage(),
            ]);
        }

        return view('console.snapshots.embed', compact(
            'project',
            'snapshot',
            'snapshotType',
            'atlasConfigScript',
        ));
    }

    public function nodes(Project $project, string $type): JsonResponse
    {
        $snapshot = $this->resolveSnapshot($project, SnapshotType::from($type));

        $nodes = $snapshot->nodes()
            ->with('databaseProperties')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (AtlasNode $node) => [
                'id' => $node->id,
                'parent_id' => $node->parent_id,
                'label' => $node->label,
                'kind' => $node->kind->value,
                'notion_page_id' => $node->notion_page_id,
                'notion_data_source_id' => $node->notion_data_source_id,
                'note' => $node->note,
                'properties' => $node->databaseProperties->map(fn ($p) => [
                    'name' => $p->name,
                    'type' => $p->property_type,
                    'options' => $p->options,
                ])->values(),
            ]);

        return response()->json(['nodes' => $nodes]);
    }

    public function import(Request $request, Project $project, string $type): RedirectResponse
    {
        $snapshotType = SnapshotType::from($type);

        $request->validate([
            'import_file' => ['required', 'file', 'mimes:json,txt', 'max:20480'],
        ]);

        $path = $request->file('import_file')->getRealPath();
        if ($path === false) {
            return back()->withErrors(['import_file' => 'Could not read uploaded file.']);
        }

        try {
            $this->importer->import($project, $snapshotType, $path);
        } catch (\Throwable $e) {
            return back()->withErrors(['import_file' => $e->getMessage()]);
        }

        return redirect()
            ->route('console.snapshots.show', [$project, $type])
            ->with('status', ucfirst($type).' snapshot imported.');
    }

    private function resolveSnapshot(Project $project, SnapshotType $type): Snapshot
    {
        return $project->snapshots()->firstOrCreate(
            ['type' => $type],
            ['meta' => []],
        );
    }
}
