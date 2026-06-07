<?php

namespace App\Enums;

enum SnapshotType: string
{
    case Before = 'before';
    case Ideal = 'ideal';
    case After = 'after';

    public function label(): string
    {
        return match ($this) {
            self::Before => 'Before',
            self::Ideal => 'Canonical',
            self::After => 'After',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
