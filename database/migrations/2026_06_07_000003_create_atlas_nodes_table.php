<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atlas_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('atlas_nodes')->cascadeOnDelete();
            $table->string('label');
            $table->string('kind');
            $table->string('notion_page_id')->nullable();
            $table->string('notion_data_source_id')->nullable();
            $table->string('color')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['snapshot_id', 'parent_id']);
            $table->index(['snapshot_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atlas_nodes');
    }
};
