<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\ColumnDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\PrimaryKeyDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationshipDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\TableDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType;

class MysqlDriver extends BaseDriver
{
    public function getTables(): Collection
    {
        return collect(
            DB::connection($this->connection)->select(
                "SELECT TABLE_NAME AS name
                 FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_TYPE = 'BASE TABLE'"
            )
        )->map(fn ($row) => new TableDTO(
            $row->name,
            $this->getColumns($row->name),
            $this->getRelationships($row->name),
            $this->getPrimaryKey($row->name)
        ));
    }

    public function getColumns(string $table): Collection
    {
        $columns = collect();

        foreach(DB::connection($this->connection)->select(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table",
            ['table' => $table]
        ) as $rawCol) {
            $columns->push(new ColumnDTO(
                name: $rawCol->COLUMN_NAME,
                type: $this->inferColumnType(explode('(', $rawCol->COLUMN_TYPE)[0]),
                rawType: $rawCol->COLUMN_TYPE,
                nullable: $rawCol->IS_NULLABLE === 'YES',
                default: $rawCol->COLUMN_DEFAULT
            ));
        }

        return $columns;
    }

    public function getRelationships(string $table): Collection
    {
        $relations = collect();

        $fks = DB::connection($this->connection)->select(
            "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            ['table' => $table]
        );

        foreach ($fks as $fk) {
            if ($fk->REFERENCED_TABLE_NAME === $table) continue;

            $relationName = $this->inferRelationName($fk->COLUMN_NAME, $fk->REFERENCED_TABLE_NAME);
            $relatedModel = Str::studly(Str::singular($fk->REFERENCED_TABLE_NAME));

            $relations->push(new RelationshipDTO(
                type: ModelRelationshipType::BELONGS_TO,
                relationName: $relationName,
                relatedModel: $relatedModel,
                relatedTable: $fk->REFERENCED_TABLE_NAME,
                foreignKey: $fk->COLUMN_NAME,
                localKey: $fk->REFERENCED_COLUMN_NAME
            ));
        }

        return $relations;
    }

    public function getPrimaryKey(string $table): ?PrimaryKeyDTO
    {
        $pk = DB::connection($this->connection)->selectOne(
            "SELECT COLUMN_NAME, COLUMN_TYPE, EXTRA
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_KEY = 'PRI'",
            ['table' => $table]
        );

        if (!$pk) return null;

        return new PrimaryKeyDTO(
            name: $pk->COLUMN_NAME,
            type: $this->inferColumnType(explode('(', $pk->COLUMN_TYPE)[0]),
            autoIncrement: str_contains(strtolower($pk->EXTRA), 'auto_increment')
        );
    }
}
