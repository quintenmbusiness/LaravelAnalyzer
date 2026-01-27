<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers;

use Illuminate\Support\Facades\DB;

class PgsqlDriver extends BaseDriver
{
    public function getTables(string $connection): array
    {
        return array_map(
            fn ($r) => $r->tablename,
            DB::connection($connection)->select(
                "SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
            )
        );
    }

    public function getTableStructure(string $connection, string $table): array
    {
        $structure = ['columns' => [], 'relationships' => []];

        foreach ($this->getColumns($connection, $table) as $col) {
            $structure['columns'][$col->column_name] = [
                'type' => $col->data_type,
                'nullable' => $col->is_nullable === 'YES',
                'default' => $col->column_default,
            ];
        }

        foreach ($this->getForeignKeys($connection, $table) as $fk) {
            $structure['relationships']['belongs_to'][] =
                $this->belongsToRelation(
                    $fk->column_name,
                    $fk->table_name,
                    $fk->ref_column
                );
        }

        return $structure;
    }

    public function getColumns(string $connection, string $table): array
    {
        return DB::connection($connection)->select(
            "SELECT column_name, data_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = 'public'
             AND table_name = :table",
            ['table' => $table]
        );
    }

    public function getForeignKeys(string $connection, string $table): array
    {
        return DB::connection($connection)->select(
            "SELECT kcu.column_name, ccu.table_name, ccu.column_name AS ref_column
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
             JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
             WHERE tc.constraint_type = 'FOREIGN KEY'
             AND tc.table_name = :table",
            ['table' => $table]
        );
    }
}
