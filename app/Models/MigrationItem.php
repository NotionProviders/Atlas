<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One page or attachment within a migration run.
 *
 * @property int $id
 * @property int $migration_run_id
 * @property ?string $external_id
 * @property string $title
 * @property string $path
 * @property int $level
 * @property string $kind
 * @property string $status
 * @property ?string $error
 * @property int $bytes
 */
class MigrationItem extends Model
{
    protected $fillable = [
        'migration_run_id', 'external_id', 'title', 'path',
        'level', 'kind', 'status', 'error', 'bytes',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(MigrationRun::class, 'migration_run_id');
    }
}
