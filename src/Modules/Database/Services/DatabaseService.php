<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationshipDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationThroughDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers\BaseDriver;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers\MysqlDriver;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers\SqliteDriver;

class DatabaseService
{
    public function getDriver(null|string $connection): BaseDriver {
        if($connection === null) {
            throw new \Exception("Connection to database cannot be null");
        }

        return match (DB::connection($connection)->getDriverName()) {
            'mysql' => new MYSQLDriver($connection),
            'sqlite' => new SQLiteDriver($connection),
        };
    }

    public function resolveInverseRelationships(Collection &$tables): void
    {
        foreach ($tables as $table) {
            foreach ($table->relations as $relation) {
                if ($relation->type !== ModelRelationshipType::BELONGS_TO) {
                    continue;
                }

                $relatedTable = $tables
                    ->first(fn ($t) => $t->name === $relation->relatedTable);

                if (! $relatedTable) {
                    continue;
                }

                $alreadyExists = $relatedTable->relations->first(fn ($r) =>
                    $r->relatedTable === $table->name
                    && in_array($r->type, [
                        ModelRelationshipType::HAS_ONE,
                        ModelRelationshipType::HAS_MANY
                    ], true)
                );

                if ($alreadyExists) {
                    continue;
                }

                $relatedTable->relations->push(
                    new RelationshipDTO(
                        type: ModelRelationshipType::HAS_MANY,
                        relationName: Str::camel(Str::plural($table->name)),
                        relatedModel: null,
                        relatedTable: $table->name,
                        foreignKey: $relation->foreignKey,
                        localKey: $relation->localKey
                    )
                );
            }
        }
    }

    public function resolveThroughRelationships(Collection &$tables): void
    {
        foreach ($tables as $table) {
            foreach ($table->relations as $sourceRelation) {
                if (
                    $sourceRelation->type !== ModelRelationshipType::HAS_ONE
                    && $sourceRelation->type !== ModelRelationshipType::HAS_MANY
                ) {
                    continue;
                }

                $throughTable = $tables
                    ->first(fn ($t) => $t->name === $sourceRelation->relatedTable);

                if (! $throughTable) {
                    continue;
                }

                foreach ($throughTable->relations as $finalRelation) {
                    if ($finalRelation->type !== ModelRelationshipType::BELONGS_TO) {
                        continue;
                    }

                    if ($finalRelation->relatedTable === $table->name) {
                        continue;
                    }

                    $alreadyExists = $table->relationsThrough->first(fn ($r) =>
                        $r->relatedTable === $finalRelation->relatedTable
                        && $r->throughTable === $throughTable->name
                    );

                    if ($alreadyExists) {
                        continue;
                    }

                    $table->relationsThrough->push(
                        new RelationThroughDTO(
                            type: $sourceRelation->type === ModelRelationshipType::HAS_ONE
                                ? ModelRelationshipType::HAS_ONE_THROUGH
                                : ModelRelationshipType::HAS_MANY_THROUGH,
                            relationName: Str::camel(Str::plural($finalRelation->relatedTable)),
                            relatedModel: null,
                            relatedTable: $finalRelation->relatedTable,
                            throughTable: $throughTable->name,
                            throughModel: null,
                            firstKey: $sourceRelation->foreignKey,
                            secondKey: $finalRelation->foreignKey,
                            localKey: $sourceRelation->localKey,
                            secondLocalKey: $finalRelation->localKey
                        )
                    );
                }
            }
        }
    }
}