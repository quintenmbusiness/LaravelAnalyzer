<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\ColumnDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\TableDTO;

abstract class BaseDriver
{
    public function __construct(public ?string $connection = null)
    {
        $this->connection = $connection ?? config('database.default');
    }

    /**
     * @return Collection<TableDTO>
     */
    public abstract function getTables(): Collection;

    /**
     * @param string $table
     * @return Collection<ColumnDTO>
     */
    public abstract function getColumns(string $table): Collection;

    public abstract function getRelationships(string $table): Collection;

    protected function belongsToRelation(string $column, string $table, string $ownerKey): array
    {
        return [
            'relation' => Str::camel(str_replace('_id', '', $column)),
            'model' => Str::studly(Str::singular($table)),
            'table' => $table,
            'foreign_key' => $column,
            'owner_key' => $ownerKey,
        ];
    }
    protected function inferRelationName(string $column, string $referencedTable): string
    {
        if (str_ends_with($column, '_id')) {
            return Str::camel(substr($column, 0, -3));
        }

        return Str::camel(Str::singular($referencedTable));
    }

    protected function inferColumnType(string $type): string
    {
        return match ($type) {
            'int', 'integer', 'bigint', 'smallint', 'tinyint' => 'number',
            'decimal', 'float', 'double', 'numeric' => 'number',
            'boolean' => 'boolean',
            'date', 'datetime', 'timestamp', 'time' => 'date',
            default => 'text',
        };
    }
}
