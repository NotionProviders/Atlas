<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single migration run: one workspace pulled out of a source tool (Loop, …)
 * by the browser extension and exported to a markdown tree. Named
 * `migration_runs` rather than `migrations` to avoid colliding with Laravel's
 * own schema-migrations table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_runs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('source')->index();            // connector key, e.g. "loop"
            $table->string('name');
            $table->string('source_ref')->nullable();      // workspace url/name at the source
            $table->string('status')->default('pending')   // pending|running|completed|failed|canceled
                ->index();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('succeeded')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('attachments')->default(0);
            $table->json('options')->nullable();
            $table->json('report')->nullable();            // Notion-readiness punch list
            $table->foreignId('token_id')->nullable()      // which paired extension ran it
                ->constrained('extension_tokens')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_runs');
    }
};
