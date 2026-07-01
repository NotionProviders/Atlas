<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A paired browser extension. Created `pending` with a short-lived pairing
 * code by the console, then activated when the extension trades the code for a
 * token. Only the token's hash is ever stored.
 *
 * @property int $id
 * @property ?string $name
 * @property ?string $token_hash
 * @property ?string $pairing_code
 * @property string $status
 * @property ?Carbon $last_used_at
 * @property ?Carbon $expires_at
 */
class ExtensionToken extends Model
{
    protected $fillable = [
        'name', 'token_hash', 'pairing_code', 'status', 'last_used_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(MigrationRun::class, 'token_id');
    }

    /**
     * Mint a pending pairing record and return [model, plaintextCode].
     *
     * @return array{0: self, 1: string}
     */
    public static function mintPairingCode(int $ttlSeconds, ?string $name = null): array
    {
        // Human-friendly, unambiguous code (no O/0/I/1).
        $code = strtoupper(Str::password(8, letters: true, numbers: true, symbols: false, spaces: false));
        $code = strtr($code, ['O' => 'R', '0' => '4', 'I' => 'X', '1' => '7', 'L' => 'M']);

        $token = static::create([
            'name' => $name,
            'status' => 'pending',
            'pairing_code' => $code,
            'expires_at' => now()->addSeconds($ttlSeconds),
        ]);

        return [$token, $code];
    }

    /**
     * Trade a pairing code for a fresh bearer token, activating the row.
     * Returns the plaintext token (shown once), or null if the code is invalid
     * or expired.
     */
    public static function redeemPairingCode(string $code, ?string $name = null): ?string
    {
        $row = static::query()
            ->where('pairing_code', $code)
            ->where('status', 'pending')
            ->first();

        if (! $row || ($row->expires_at !== null && $row->expires_at->isPast())) {
            return null;
        }

        $plain = Str::random(48);

        $row->forceFill([
            'token_hash' => hash('sha256', $plain),
            'pairing_code' => null,
            'status' => 'active',
            'expires_at' => null,
            'name' => $name ?: $row->name,
            'last_used_at' => now(),
        ])->save();

        return $plain;
    }

    /**
     * Resolve an active token from a plaintext bearer value.
     */
    public static function resolve(string $plain): ?self
    {
        return static::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where('status', 'active')
            ->first();
    }

    public function touchUsage(): void
    {
        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }
}
