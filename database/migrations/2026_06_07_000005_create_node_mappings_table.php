<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('node_mappings');
    }
};
