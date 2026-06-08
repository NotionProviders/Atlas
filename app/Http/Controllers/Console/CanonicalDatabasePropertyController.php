<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\CanonicalDatabase;
use App\Models\CanonicalDatabaseProperty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CanonicalDatabasePropertyController extends Controller
{
    /** @var list<string> */
    public const PROPERTY_TYPES = [
        'title',
        'rich_text',
        'number',
        'select',
        'multi_select',
        'status',
        'date',
        'checkbox',
        'url',
        'email',
        'phone_number',
        'files',
        'person',
        'relation',
        'rollup',
        'formula',
        'created_time',
        'created_by',
        'last_edited_time',
        'last_edited_by',
    ];

    public function store(Request $request, CanonicalDatabase $canonicalDatabase): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', Rule::in(self::PROPERTY_TYPES)],
        ]);

        $isTitle = strcasecmp($validated['name'], 'Name') === 0
            || $validated['property_type'] === 'title';

        $sortOrder = (int) $canonicalDatabase->properties()->max('sort_order') + 1;

        $canonicalDatabase->properties()->create([
            'name' => $validated['name'],
            'property_type' => $isTitle ? 'title' : $validated['property_type'],
            'is_title' => $isTitle,
            'sort_order' => $sortOrder,
        ]);

        return back()->with('status', 'Property "'.$validated['name'].'" added.');
    }

    public function update(
        Request $request,
        CanonicalDatabase $canonicalDatabase,
        CanonicalDatabaseProperty $property,
    ): RedirectResponse {
        abort_unless($property->canonical_database_id === $canonicalDatabase->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', Rule::in(self::PROPERTY_TYPES)],
        ]);

        $isTitle = strcasecmp($validated['name'], 'Name') === 0
            || $validated['property_type'] === 'title';

        $property->update([
            'name' => $validated['name'],
            'property_type' => $isTitle ? 'title' : $validated['property_type'],
            'is_title' => $isTitle,
        ]);

        return back()->with('status', 'Property updated.');
    }

    public function destroy(
        CanonicalDatabase $canonicalDatabase,
        CanonicalDatabaseProperty $property,
    ): RedirectResponse {
        abort_unless($property->canonical_database_id === $canonicalDatabase->id, 404);

        if ($property->is_title) {
            return back()->withErrors(['property' => 'The title property cannot be removed.']);
        }

        $name = $property->name;
        $property->delete();

        return back()->with('status', 'Removed property "'.$name.'".');
    }
}
