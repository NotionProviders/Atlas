<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTeamspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectTeamspaceController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'assign_atlas_node_id' => ['nullable', 'integer', 'exists:atlas_nodes,id'],
        ]);

        $sortOrder = (int) $project->teamspaces()->max('sort_order') + 1;

        $teamspace = $project->teamspaces()->create([
            'name' => trim($validated['name']),
            'notes' => $validated['notes'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        if ($assignNodeId = $validated['assign_atlas_node_id'] ?? null) {
            $mapping = $project->databaseMappings()
                ->where('atlas_node_id', $assignNodeId)
                ->first();

            if ($mapping) {
                $mapping->update(['project_teamspace_id' => $teamspace->id]);
            }
        }

        return back()->with('status', 'Teamspace "'.trim($validated['name']).'" added.');
    }

    public function destroy(Project $project, ProjectTeamspace $teamspace): RedirectResponse
    {
        abort_unless($teamspace->project_id === $project->id, 404);

        $name = $teamspace->name;
        $teamspace->delete();

        return back()->with('status', 'Removed teamspace "'.$name.'".');
    }
}
