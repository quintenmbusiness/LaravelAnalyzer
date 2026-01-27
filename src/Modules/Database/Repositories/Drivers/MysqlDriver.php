<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers;

use Illuminate\Support\Facades\DB;

class MysqlDriver extends BaseDriver
{
    public function getTables(string $connection): array
    {
        return array_map(fn ($r) => $r->name, DB::connection($connection)->select(
            "SELECT TABLE_NAME AS name FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_TYPE = 'BASE TABLE'"
            )
        );
    }

    public function getTableStructure(string $connection, string $table): array
    {
        $structure = ['columns' => [], 'relationships' => []];

        foreach ($this->getColumns($connection, $table) as $col) {
            $structure['columns'][$col->COLUMN_NAME] = [
                'type' => $col->COLUMN_TYPE,
                'nullable' => $col->IS_NULLABLE === 'YES',
                'default' => $col->COLUMN_DEFAULT,
            ];
        }

        foreach ($this->getForeignKeys($connection, $table) as $fk) {
            $structure['relationships']['belongs_to'][] =
                $this->belongsToRelation(
                    $fk->COLUMN_NAME,
                    $fk->REFERENCED_TABLE_NAME,
                    $fk->REFERENCED_COLUMN_NAME
                );
        }

        return $structure;
    }

    public function getColumns(string $connection, string $table): array
    {
        return DB::connection($connection)->select(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :table",
                ['table' => $table]
        );
    }

    public function getForeignKeys(string $connection, string $table): array
    {
       return DB::connection($connection)->select(
           "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table
            AND REFERENCED_TABLE_NAME IS NOT NULL",
            ['table' => $table]
       );
    }
}
