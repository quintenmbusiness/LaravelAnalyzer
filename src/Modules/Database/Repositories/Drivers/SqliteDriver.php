<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\ColumnDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationshipDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\TableDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType;

class SqliteDriver extends BaseDriver
{
    public function getTables(): Collection
    {
        return collect(
            DB::connection($this->connection)->select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
            )
        )->map(
            fn ($row) => new TableDTO(
                $row->name,
                $this->getColumns($row->name),
                $this->getRelationships($row->name)
            )
        );
    }

    public function getColumns(string $table): Collection
    {
        $columns = collect();

        foreach (
            DB::connection($this->connection)->select(
                "PRAGMA table_info(\"$table\")"
            ) as $col
        ) {
            $columns->push(
                new ColumnDTO(
                    name: $col->name,
                    type: $this->inferColumnType($col->type),
                    rawType: $col->type,
                    nullable: ! $col->notnull,
                    default: $col->dflt_value
                )
            );
        }

        return $columns;
    }

    public function getRelationships(string $table): Collection
    {
        $relations = collect();

        foreach (
            DB::connection($this->connection)->select(
                "PRAGMA foreign_key_list(\"$table\")"
            ) as $fk
        ) {
            if ($fk->table === $table) {
                continue;
            }

            $relationName = $this->inferRelationName($fk->from, $fk->table);
            $relatedModel = Str::studly(Str::singular($fk->table));

            $relations->push(
                new RelationshipDTO(
                    type: ModelRelationshipType::BELONGS_TO,
                    relationName: $relationName,
                    relatedModel: $relatedModel,
                    relatedTable: $fk->table,
                    foreignKey: $fk->from,
                    localKey: $fk->to
                )
            );
        }

        return $relations;
    }
}
