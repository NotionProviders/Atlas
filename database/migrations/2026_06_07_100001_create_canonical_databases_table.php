<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canonical_databases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('canonical_database_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canonical_database_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('property_type');
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('database_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('atlas_node_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canonical_database_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'atlas_node_id']);
        });

        Schema::dropIfExists('node_mappings');
    }

    public function down(): void
    {
        Schema::dropIfExists('database_mappings');
        Schema::dropIfExists('canonical_database_properties');
        Schema::dropIfExists('canonical_databases');

        Schema::create('node_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_node_id')->constrained('atlas_nodes')->cascadeOnDelete();
            $table->foreignId('target_node_id')->constrained('atlas_nodes')->cascadeOnDelete();
            $table->string('mapping_type');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['source_node_id', 'target_node_id']);
        });
    }
};
