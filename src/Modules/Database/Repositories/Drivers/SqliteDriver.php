<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers;

use Illuminate\Support\Facades\DB;

class SqliteDriver extends BaseDriver
{
    public function getTables(string $connection): array
    {
        return array_map(
            fn ($r) => $r->name,
            DB::connection($connection)->select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
            )
        );
    }

    public function getTableStructure(string $connection, string $table): array
    {
        $structure = ['columns' => [], 'relationships' => []];

        foreach ($this->getColumns($connection, $table) as $col) {
            $structure['columns'][$col->name] = [
                'type' => strtolower($col->type),
                'nullable' => $col->notnull === 0,
                'default' => $col->dflt_value,
                'primary' => $col->pk === 1,
            ];
        }

        foreach ($this->getForeignKeys($connection, $table) as $fk) {
            $structure['relationships']['belongs_to'][] =
                $this->belongsToRelation(
                    $fk->from,
                    $fk->table,
                    $fk->to
                );
        }

        return $structure;
    }

    public function getColumns(string $connection, string $table): array
    {
        return DB::connection($connection)->select(
            "PRAGMA table_info(\"$table\")"
        );
    }

    public function getForeignKeys(string $connection, string $table): array
    {
        return DB::connection($connection)->select(
            "PRAGMA foreign_key_list(\"$table\")"
        );
    }
}
