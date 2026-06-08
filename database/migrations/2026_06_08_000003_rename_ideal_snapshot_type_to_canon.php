<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('snapshots')->where('type', 'ideal')->update(['type' => 'canon']);
    }

    public function down(): void
    {
        DB::table('snapshots')->where('type', 'canon')->update(['type' => 'ideal']);
    }
};
