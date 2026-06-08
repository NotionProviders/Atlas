<?php

namespace App\Services;

use App\Models\DatabaseMapping;
use App\Models\DatabaseMappingProperty;

class MappingPropertySyncService
{
    public function syncFromCanonical(DatabaseMapping $mapping): void
    {
        $mapping->properties()
            ->where('source', DatabaseMappingProperty::SOURCE_CANONICAL)
            ->delete();

        $mapping->load('canonicalDatabase.properties');

        foreach ($mapping->canonicalDatabase->properties as $index => $prop) {
            $mapping->properties()->create([
                'name' => $prop->name,
                'property_type' => $prop->property_type,
                'source' => DatabaseMappingProperty::SOURCE_CANONICAL,
                'is_locked' => true,
                'source_canonical_property_id' => $prop->id,
                'is_title' => $prop->is_title,
                'sort_order' => $index,
            ]);
        }
    }
}
