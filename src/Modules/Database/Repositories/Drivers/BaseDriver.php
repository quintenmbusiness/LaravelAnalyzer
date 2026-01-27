<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class BaseDriver
{
    public string $driver;

    public function __construct(public ?string $connection = null)
    {
        $this->connection = $connection ?? config('database.default');
        $this->driver = DB::connection($connection)->getDriverName();
    }

    public abstract function getTables(string $connection): array;
    public abstract function getTableStructure(string $connection, string $table): array;
    public abstract function getColumns(string $connection, string $table): array;
    public abstract function getForeignKeys(string $connection, string $table): array;

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
}
