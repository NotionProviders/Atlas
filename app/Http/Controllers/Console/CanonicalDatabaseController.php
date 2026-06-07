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
        $canonicalDatabases = CanonicalDatabase::query()
            ->with('properties')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('console.canonical.index', compact('canonicalDatabases'));
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
}
