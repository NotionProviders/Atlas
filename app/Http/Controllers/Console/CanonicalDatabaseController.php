<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\CanonicalDatabase;
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

    public function panel(CanonicalDatabase $canonicalDatabase): View
    {
        return view('console.canonical.panel', $this->detailContext($canonicalDatabase));
    }

    public function show(CanonicalDatabase $canonicalDatabase): View
    {
        return view('console.canonical.show', $this->detailContext($canonicalDatabase));
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
    private function detailContext(CanonicalDatabase $canonicalDatabase): array
    {
        $canonicalDatabase->load('properties');

        $mappingCount = $canonicalDatabase->databaseMappings()->count();
        $projectCount = (int) $canonicalDatabase->databaseMappings()
            ->distinct('project_id')
            ->count('project_id');

        return compact('canonicalDatabase', 'mappingCount', 'projectCount');
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
