<?php

namespace App\Enums;

enum NodeKind: string
{
    case Workspace = 'workspace';
    case Teamspace = 'teamspace';
    case Page = 'page';
    case Database = 'database';
    case Row = 'row';
    case Property = 'property';

    public function label(): string
    {
        return match ($this) {
            self::Workspace => 'Workspace',
            self::Teamspace => 'Teamspace',
            self::Page => 'Page',
            self::Database => 'Database',
            self::Row => 'Row',
            self::Property => 'Property',
        };
    }
}
