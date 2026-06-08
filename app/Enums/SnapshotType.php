<?php

namespace App\Enums;

enum SnapshotType: string
{
    case Before = 'before';
    case Canon = 'canon';
    case After = 'after';

    public function label(): string
    {
        return match ($this) {
            self::Before => 'Before',
            self::Canon => 'Canonical',
            self::After => 'After',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
