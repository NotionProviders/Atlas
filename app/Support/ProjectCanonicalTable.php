<?php

namespace App\Support;

use App\Models\CanonicalDatabase;
use App\Models\DatabaseMapping;
use App\Models\Project;

class ProjectCanonicalTable
{
    /** @return \Illuminate\Support\Collection<int, object{canonical: CanonicalDatabase, placement: mixed, mappings: \Illuminate\Support\Collection}> */
    public static function rows(Project $project)
    {
        $mappingsByNodeId = $project->databaseMappings()
            ->with(['canonicalDatabase.properties', 'atlasNode'])
            ->get()
            ->keyBy('atlas_node_id');

        $placementsByCanonicalId = $project->canonicalPlacements()
            ->with('teamspaceNode')
            ->get()
            ->keyBy('canonical_database_id');

        $mappedCanonicalIds = $mappingsByNodeId->pluck('canonical_database_id')->unique();

        return CanonicalDatabase::query()
            ->with('properties')
            ->whereIn('id', $mappedCanonicalIds)
            ->orderBy('is_custom')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (CanonicalDatabase $db) use ($mappingsByNodeId, $placementsByCanonicalId) {
                $mappings = $mappingsByNodeId
                    ->filter(fn (DatabaseMapping $m) => $m->canonical_database_id === $db->id)
                    ->values();

                return (object) [
                    'canonical' => $db,
                    'placement' => $placementsByCanonicalId->get($db->id),
                    'mappings' => $mappings,
                ];
            })
            ->values();
    }
}
