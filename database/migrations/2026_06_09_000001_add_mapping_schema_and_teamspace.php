<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('database_mappings', function (Blueprint $table) {
            $table->foreignId('teamspace_node_id')->nullable()->after('migration_details')->constrained('atlas_nodes')->nullOnDelete();
            $table->text('placement_notes')->nullable()->after('teamspace_node_id');
        });

        if (Schema::hasTable('canonical_placements')) {
            $placements = DB::table('canonical_placements')->get();

            foreach ($placements as $placement) {
                DB::table('database_mappings')
                    ->where('project_id', $placement->project_id)
                    ->where('canonical_database_id', $placement->canonical_database_id)
                    ->whereNull('teamspace_node_id')
                    ->update([
                        'teamspace_node_id' => $placement->teamspace_node_id,
                        'placement_notes' => $placement->placement_notes,
                    ]);
            }
        }

        Schema::create('database_mapping_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_mapping_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('property_type');
            $table->string('source', 16)->default('custom');
            $table->boolean('is_locked')->default(false);
            $table->foreignId('source_canonical_property_id')->nullable()->constrained('canonical_database_properties')->nullOnDelete();
            $table->foreignId('source_client_property_id')->nullable()->constrained('database_properties')->nullOnDelete();
            $table->text('merge_notes')->nullable();
            $table->boolean('is_title')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $mappings = DB::table('database_mappings')->get();

        foreach ($mappings as $mapping) {
            $canonicalProperties = DB::table('canonical_database_properties')
                ->where('canonical_database_id', $mapping->canonical_database_id)
                ->orderBy('sort_order')
                ->get();

            foreach ($canonicalProperties as $index => $prop) {
                DB::table('database_mapping_properties')->insert([
                    'database_mapping_id' => $mapping->id,
                    'name' => $prop->name,
                    'property_type' => $prop->property_type,
                    'source' => 'canonical',
                    'is_locked' => true,
                    'source_canonical_property_id' => $prop->id,
                    'is_title' => (bool) $prop->is_title,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('database_mapping_properties');

        Schema::table('database_mappings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teamspace_node_id');
            $table->dropColumn('placement_notes');
        });
    }
};
