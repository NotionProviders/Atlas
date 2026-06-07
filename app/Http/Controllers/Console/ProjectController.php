<?php

namespace App\Http\Controllers\Console;

use App\Enums\SnapshotType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->with('snapshots')
            ->latest()
            ->get();

        return view('console.projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('console.projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $slug = Str::slug($validated['name']);
        $base = $slug;
        $i = 1;
        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        $project = Project::query()->create([
            ...$validated,
            'slug' => $slug,
            'user_id' => $request->user()->id,
        ]);

        foreach (SnapshotType::cases() as $type) {
            $project->snapshots()->create(['type' => $type]);
        }

        return redirect()
            ->route('console.projects.show', $project)
            ->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        $project->load('snapshots');

        $snapshotsByType = $project->snapshots->keyBy(fn ($s) => $s->type->value);

        $before = $snapshotsByType[SnapshotType::Before->value] ?? null;
        $after = $snapshotsByType[SnapshotType::After->value] ?? null;

        $previewTypes = collect(SnapshotType::cases())
            ->filter(function (SnapshotType $type) use ($snapshotsByType) {
                $snapshot = $snapshotsByType[$type->value] ?? null;

                return $snapshot && ! $snapshot->isEmpty();
            })
            ->values();

        $defaultPreviewType = null;
        if ($after && ! $after->isEmpty()) {
            $defaultPreviewType = SnapshotType::After;
        } elseif ($before && ! $before->isEmpty()) {
            $defaultPreviewType = SnapshotType::Before;
        }

        return view('console.projects.show', compact(
            'project',
            'snapshotsByType',
            'previewTypes',
            'defaultPreviewType',
        ));
    }

    public function edit(Project $project): View
    {
        return view('console.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $project->update($validated);

        return redirect()
            ->route('console.projects.show', $project)
            ->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('console.projects.index')
            ->with('status', 'Project deleted.');
    }
}
