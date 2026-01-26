<?php

namespace quintenmbusiness\LaravelAnalyzer\Enums;

enum ModelRelationshipType: string
{
    case BELONGS_TO = 'belongsTo';
    case HAS_ONE = 'hasOne';
    case HAS_MANY = 'hasMany';
    case BELONGS_TO_MANY = 'belongsToMany';
    case MORPH_TO = 'morphTo';
    case MORPH_ONE = 'morphOne';
    case MORPH_MANY = 'morphMany';
    case HAS_MANY_THROUGH = 'hasManyThrough';
    case HAS_ONE_THROUGH = 'hasOneThrough';

    public function methodName(): string
    {
        return $this->value;
    }

    public function isInverse(): bool
    {
        return match ($this) {
            self::BELONGS_TO,
            self::MORPH_TO => true,
            default => false,
        };
    }

    public function isCollection(): bool
    {
        return match ($this) {
            self::HAS_MANY,
            self::BELONGS_TO_MANY,
            self::MORPH_MANY,
            self::HAS_MANY_THROUGH => true,
            default => false,
        };
    }

    public static function basic(): array
    {
        return [
            self::BELONGS_TO,
            self::HAS_ONE,
            self::HAS_MANY,
        ];
    }

    public static function polymorphic(): array
    {
        return [
            self::MORPH_TO,
            self::MORPH_ONE,
            self::MORPH_MANY,
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
