<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bearer tokens for the browser extension. A console session mints a row in
 * `pending` state carrying a short-lived pairing_code; the extension trades
 * that code for a token, at which point the row goes `active` and stores only
 * the SHA-256 hash of the token (never the token itself).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();               // "Chrome on Brad's PC"
            $table->string('token_hash')->nullable()->index(); // sha256(token)
            $table->string('pairing_code')->nullable()->unique();
            $table->string('status')->default('pending')->index(); // pending|active|revoked
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();       // pairing-code expiry
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_tokens');
    }
};
