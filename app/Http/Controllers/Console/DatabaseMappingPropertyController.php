<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\AtlasNode;
use App\Models\DatabaseMappingProperty;
use App\Models\DatabaseProperty;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DatabaseMappingPropertyController extends Controller
{
    public function store(Request $request, Project $project, AtlasNode $atlasNode): RedirectResponse
    {
        $mapping = $this->mappingForNode($project, $atlasNode);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', Rule::in(CanonicalDatabasePropertyController::PROPERTY_TYPES)],
        ]);

        $isTitle = strcasecmp($validated['name'], 'Name') === 0
            || $validated['property_type'] === 'title';

        $sortOrder = (int) $mapping->properties()->max('sort_order') + 1;

        $mapping->properties()->create([
            'name' => $validated['name'],
            'property_type' => $isTitle ? 'title' : $validated['property_type'],
            'source' => DatabaseMappingProperty::SOURCE_CUSTOM,
            'is_locked' => false,
            'is_title' => $isTitle,
            'sort_order' => $sortOrder,
        ]);

        return $this->redirectBack($project, $atlasNode, 'Property "'.$validated['name'].'" added.');
    }

    public function mergeClient(
        Project $project,
        AtlasNode $atlasNode,
        DatabaseProperty $clientProperty,
    ): RedirectResponse {
        $mapping = $this->mappingForNode($project, $atlasNode);
        abort_unless($clientProperty->atlas_node_id === $atlasNode->id, 404);

        if ($mapping->properties()->where('source_client_property_id', $clientProperty->id)->exists()) {
            return $this->redirectBack($project, $atlasNode, 'That client property is already in the schema.');
        }

        $sortOrder = (int) $mapping->properties()->max('sort_order') + 1;

        $mapping->properties()->create([
            'name' => $clientProperty->name,
            'property_type' => $clientProperty->property_type,
            'source' => DatabaseMappingProperty::SOURCE_CLIENT,
            'is_locked' => false,
            'source_client_property_id' => $clientProperty->id,
            'is_title' => $clientProperty->property_type === 'title',
            'sort_order' => $sortOrder,
        ]);

        return $this->redirectBack($project, $atlasNode, 'Merged client property "'.$clientProperty->name.'".');
    }

    public function update(
        Request $request,
        Project $project,
        AtlasNode $atlasNode,
        DatabaseMappingProperty $property,
    ): RedirectResponse {
        $mapping = $this->mappingForNode($project, $atlasNode);
        abort_unless($property->database_mapping_id === $mapping->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', Rule::in(CanonicalDatabasePropertyController::PROPERTY_TYPES)],
            'merge_notes' => ['nullable', 'string'],
        ]);

        $isTitle = strcasecmp($validated['name'], 'Name') === 0
            || $validated['property_type'] === 'title';

        $property->update([
            'name' => $validated['name'],
            'property_type' => $isTitle ? 'title' : $validated['property_type'],
            'is_title' => $isTitle,
            'merge_notes' => $validated['merge_notes'] ?? null,
        ]);

        return $this->redirectBack($project, $atlasNode, 'Property updated.');
    }

    public function destroy(
        Project $project,
        AtlasNode $atlasNode,
        DatabaseMappingProperty $property,
    ): RedirectResponse {
        $mapping = $this->mappingForNode($project, $atlasNode);
        abort_unless($property->database_mapping_id === $mapping->id, 404);

        if ($property->is_locked) {
            return back()->withErrors(['property' => 'Template properties copied from the canonical database cannot be removed.']);
        }

        if ($property->is_title) {
            return back()->withErrors(['property' => 'The title property cannot be removed.']);
        }

        $name = $property->name;
        $property->delete();

        return $this->redirectBack($project, $atlasNode, 'Removed property "'.$name.'".');
    }

    private function mappingForNode(Project $project, AtlasNode $atlasNode)
    {
        return $project->databaseMappings()
            ->where('atlas_node_id', $atlasNode->id)
            ->firstOrFail();
    }

    private function redirectBack(Project $project, AtlasNode $atlasNode, string $message): RedirectResponse
    {
        return redirect()
            ->route('console.mappings.database.show', [$project, $atlasNode])
            ->with('status', $message);
    }
}
