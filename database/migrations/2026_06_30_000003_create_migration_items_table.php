<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One page (or attachment) within a migration run. The markdown body itself
 * lives on disk under the run's export tree; this row is the index + status so
 * the console can show live per-page progress and a punch list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_run_id')->constrained('migration_runs')->cascadeOnDelete();
            $table->string('external_id')->nullable();     // source node id, if any
            $table->string('title');
            $table->string('path', 1024)->default('');      // relative export path
            $table->unsignedSmallInteger('level')->default(0);
            $table->string('kind')->default('page');        // page|attachment
            $table->string('status')->default('pending');   // pending|ok|failed|empty|skipped
            $table->text('error')->nullable();
            $table->unsignedInteger('bytes')->default(0);
            $table->timestamps();

            $table->index(['migration_run_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_items');
    }
};
