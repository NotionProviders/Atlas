<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canonical_databases', function (Blueprint $table) {
            $table->string('notion_export_id', 32)->nullable()->after('slug');
            $table->string('template_tag', 16)->nullable()->after('notion_export_id');
            $table->boolean('is_lookup')->default(false)->after('template_tag');

            $table->index('notion_export_id');
            $table->index('template_tag');
        });

        Schema::table('canonical_database_properties', function (Blueprint $table) {
            $table->boolean('is_title')->default(false)->after('property_type');
        });

        Schema::table('database_mappings', function (Blueprint $table) {
            $table->index('canonical_database_id');
        });
    }

    public function down(): void
    {
        Schema::table('database_mappings', function (Blueprint $table) {
            $table->dropIndex(['canonical_database_id']);
        });

        Schema::table('canonical_database_properties', function (Blueprint $table) {
            $table->dropColumn('is_title');
        });

        Schema::table('canonical_databases', function (Blueprint $table) {
            $table->dropIndex(['notion_export_id']);
            $table->dropIndex(['template_tag']);
            $table->dropColumn(['notion_export_id', 'template_tag', 'is_lookup']);
        });
    }
};
