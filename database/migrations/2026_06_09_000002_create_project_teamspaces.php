<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_teamspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });

        Schema::table('database_mappings', function (Blueprint $table) {
            $table->foreignId('project_teamspace_id')
                ->nullable()
                ->after('migration_details')
                ->constrained('project_teamspaces')
                ->nullOnDelete();
        });

        Schema::table('database_mappings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teamspace_node_id');
        });

        if (Schema::hasColumn('database_mapping_properties', 'is_locked')) {
            \Illuminate\Support\Facades\DB::table('database_mapping_properties')
                ->where('source', 'canonical')
                ->update(['is_locked' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('database_mappings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_teamspace_id');
            $table->foreignId('teamspace_node_id')->nullable()->constrained('atlas_nodes')->nullOnDelete();
        });

        Schema::dropIfExists('project_teamspaces');
    }
};
