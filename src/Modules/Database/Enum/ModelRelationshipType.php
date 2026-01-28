<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum;

enum ModelRelationshipType: string
{
    case BELONGS_TO = 'belongsTo';
    case HAS_ONE = 'hasOne';
    case HAS_MANY = 'hasMany';
    case HAS_MANY_THROUGH = 'hasManyThrough';
    case HAS_ONE_THROUGH = 'hasOneThrough';


    public static function basic(): array
    {
        return [
            self::BELONGS_TO,
            self::HAS_ONE,
            self::HAS_MANY,
        ];
    }


    public static function through(): array
    {
        return [
            self::HAS_MANY_THROUGH,
            self::HAS_ONE_THROUGH,
        ];
    }
}
