<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->timestamp('imported_at')->nullable();
            $table->json('meta')->nullable();
            $table->json('tree_json')->nullable();
            $table->json('palette')->nullable();
            $table->json('legend')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshots');
    }
};
