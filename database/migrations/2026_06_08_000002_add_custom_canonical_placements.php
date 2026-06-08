<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canonical_databases', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('is_lookup');
        });

        Schema::table('database_mappings', function (Blueprint $table) {
            $table->text('migration_details')->nullable()->after('notes');
        });

        Schema::create('canonical_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canonical_database_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teamspace_node_id')->nullable()->constrained('atlas_nodes')->nullOnDelete();
            $table->text('placement_notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'canonical_database_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_placements');

        Schema::table('database_mappings', function (Blueprint $table) {
            $table->dropColumn('migration_details');
        });

        Schema::table('canonical_databases', function (Blueprint $table) {
            $table->dropColumn('is_custom');
        });
    }
};
