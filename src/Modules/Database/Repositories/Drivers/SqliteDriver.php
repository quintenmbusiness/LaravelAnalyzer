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

class SqliteDriver extends BaseDriver
{
    public function getTables(): Collection
    {
        return collect(
            DB::connection($this->connection)->select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
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

        foreach (DB::connection($this->connection)->select("PRAGMA table_info(\"$table\")") as $col) {
            $columns->push(new ColumnDTO(
                name: $col->name,
                type: $this->inferColumnType($col->type),
                rawType: $col->type,
                nullable: $col->notnull == 0,
                default: $col->dflt_value
            ));
        }

        return $columns;
    }

    public function getRelationships(string $table): Collection
    {
        $relations = collect();

        foreach (DB::connection($this->connection)->select("PRAGMA foreign_key_list(\"$table\")") as $fk) {
            if ($fk->table === $table) continue;

            $relationName = $this->inferRelationName($fk->from, $fk->table);
            $relatedModel = Str::studly(Str::singular($fk->table));

            $relations->push(new RelationshipDTO(
                type: ModelRelationshipType::BELONGS_TO,
                relationName: $relationName,
                relatedModel: $relatedModel,
                relatedTable: $fk->table,
                foreignKey: $fk->from,
                localKey: $fk->to
            ));
        }

        return $relations;
    }

    public function getPrimaryKey(string $table): ?PrimaryKeyDTO
    {
        $pkColumn = DB::connection($this->connection)->selectOne("PRAGMA table_info(\"$table\") WHERE pk = 1");

        if (!$pkColumn) return null;

        $autoIncrement = false;

        // Detect AUTOINCREMENT (SQLite stores it only in table creation SQL)
        $createTableSql = DB::connection($this->connection)
            ->selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);

        if ($createTableSql && str_contains(strtolower($createTableSql->sql), $pkColumn->name . ' INTEGER PRIMARY KEY AUTOINCREMENT')) {
            $autoIncrement = true;
        }

        return new PrimaryKeyDTO(
            name: $pkColumn->name,
            type: $this->inferColumnType($pkColumn->type),
            autoIncrement: $autoIncrement
        );
    }
}
