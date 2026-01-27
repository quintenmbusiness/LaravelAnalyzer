<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\ColumnDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\DatabaseDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\ModelDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\ModelRelationDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\ModelRelationThroughDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\TableDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class DatabaseModule
{
    protected array $classMap;

    public function __construct(?array $classMap = null)
    {
        $this->classMap = $classMap ?? $this->loadComposerClassMap();
    }

    public function getDatabase(): DatabaseDTO
    {
        $database = new DatabaseDTO();

        $models = $this->discoverModelClasses();
        $structures = $this->getDatabaseStructure();

        $modelByTable = [];

        foreach ($models as $class) {
            $table = $this->inferTableNameFromModelClass($class);
            if ($table) {
                $modelByTable[$table] = $class;
            }
        }

        // Step 1: Create initial table objects with columns and basic relationships
        $tableObjects = [];

        foreach ($structures as $tableName => $tableStructure) {
            try {
                $columns = collect();
                foreach ($tableStructure['columns'] as $name => $data) {
                    $columns->push(new ColumnDTO(
                        $name,
                        $data['type'],
                        $data['raw_type'],
                        $data['nullable'],
                        $data['default'] ?? null
                    ));
                }

                $modelRelations = collect();
                foreach ($tableStructure['relationships'] as $group => $relations) {
                    foreach ($relations as $relation) {
                        $type = match ($group) {
                            'belongs_to' => ModelRelationshipType::BELONGS_TO,
                            'has_one' => ModelRelationshipType::HAS_ONE,
                            'has_many' => ModelRelationshipType::HAS_MANY,
                        };

                        $relatedTable = $relation['table'];
                        $relatedModel = $modelByTable[$relatedTable] ?? null;

                        $modelRelations->push(new ModelRelationDTO(
                            $type,
                            $relation['relation'],
                            $relatedModel,
                            $relatedTable,
                            $relation['foreign_key'],
                            $relation['owner_key'] ?? $relation['local_key']
                        ));
                    }
                }

                $modelClass = $modelByTable[$tableName] ?? null;

                $model = new ModelDTO(
                    $modelClass ? class_basename($modelClass) : null,
                    $modelClass,
                    $modelRelations
                );

                $tableObjects[$tableName] = new TableDTO(
                    $tableName,
                    $model,
                    $columns
                );
            } catch (Throwable $e) {
                continue;
            }
        }

        // Step 2: Detect potential HasManyThrough / HasOneThrough relationships
        foreach ($tableObjects as $table) {
            foreach ($table->model->relations as $relation) {
                if ($relation->type !== ModelRelationshipType::HAS_MANY && $relation->type !== ModelRelationshipType::HAS_ONE) {
                    continue;
                }

                $intermediateTable = $relation->relatedTable;

                if (!isset($tableObjects[$intermediateTable])) {
                    continue;
                }

                $intermediateRelations = $tableObjects[$intermediateTable]->model->relations;

                foreach ($intermediateRelations as $intermediateRel) {
                    // Only consider belongsTo or hasOne for through detection
                    if (!in_array($intermediateRel->type, [ModelRelationshipType::BELONGS_TO, ModelRelationshipType::HAS_ONE])) {
                        continue;
                    }

                    if ($intermediateRel->relatedTable === $table->name) {
                        continue; // avoid loops
                    }

                    $table->model->throughRelations->push(new ModelRelationThroughDTO(
                        $relation->type === ModelRelationshipType::HAS_ONE
                            ? ModelRelationshipType::HAS_ONE_THROUGH
                            : ModelRelationshipType::HAS_MANY_THROUGH,
                        Str::camel(Str::plural($intermediateRel->relatedTable)),
                        $intermediateRel->relatedModel,          // final related model class or null
                        $intermediateRel->relatedTable,          // final related table
                        $intermediateTable,                       // intermediate table
                        $tableObjects[$intermediateTable]->model->path ?? null, // intermediate model class or null
                        $relation->foreignKey,                    // firstKey: intermediate table foreign key pointing to current table
                        $intermediateRel->foreignKey,            // secondKey: final table foreign key pointing to intermediate table
                        $relation->localKey,                      // localKey: primary key on current table
                        $intermediateRel->localKey ?? $intermediateRel->ownerKey // secondLocalKey: primary key on intermediate table
                    ));

                }
            }
        }

        // Step 3: Push final table objects to database
        foreach ($tableObjects as $table) {
            $database->tables->push($table);
        }

        return $database;
    }



    protected function discoverModelClasses(): array
    {
        $classes = [];
        foreach ($this->classMap as $class => $path) {
            if ($this->isModelNamespace($class) && $this->isEloquentModel($class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    protected function loadComposerClassMap(): array
    {
        $path = base_path('vendor/composer/autoload_classmap.php');

        if (!is_file($path)) {
            return [];
        }

        return require $path;
    }

    protected function isModelNamespace(string $class): bool
    {
        return str_starts_with($class, 'App\\Models\\');
    }

    protected function isEloquentModel(string $class): bool
    {
        return class_exists($class) && is_subclass_of($class, Model::class);
    }

    protected function inferTableNameFromModelClass(string $class): ?string
    {
        if (!class_exists($class)) {
            return null;
        }

        try {
            $ref = new ReflectionClass($class);
            if ($ref->hasProperty('table')) {
                $props = $ref->getDefaultProperties();
                if (!empty($props['table'])) {
                    return $props['table'];
                }
            }
        } catch (Throwable) {
        }

        return Str::snake(Str::plural(class_basename($class)));
    }

    public function getAllTables(string $connection = null): array
    {
        $connection = $connection ?? config('database.default');
        $driver = DB::connection($connection)->getDriverName();

        if ($driver === 'sqlite') {
            return array_map(
                fn($r) => $r->name,
                DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")
            );
        }

        if ($driver === 'mysql') {
            return array_map(
                fn($r) => $r->name,
                DB::connection($connection)->select("SELECT TABLE_NAME as name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'")
            );
        }

        if ($driver === 'pgsql') {
            return array_map(
                fn($r) => $r->name,
                DB::connection($connection)->select("SELECT tablename as name FROM pg_tables WHERE schemaname = 'public'")
            );
        }

        return Schema::connection($connection)->getConnection()->getSchemaBuilder()->getAllTables();
    }

    public function getDatabaseStructure(string $connection = null): array
    {
        $connection = $connection ?? config('database.default');
        $driver = DB::connection($connection)->getDriverName();
        $tables = $this->getAllTables($connection);

        $structures = [];

        foreach ($tables as $table) {
            $structures[$table] = [
                'columns' => [],
                'relationships' => [
                    'belongs_to' => [],
                    'has_many' => [],
                    'has_one' => [],
                ],
            ];
        }

        foreach ($tables as $table) {
            if ($driver === 'sqlite') {
                $cols = DB::connection($connection)->select("PRAGMA table_info(\"$table\")");

                foreach ($cols as $c) {
                    $structures[$table]['columns'][$c->name] = [
                        'type' => $this->inferColumnType(strtolower($c->type ?? '')),
                        'raw_type' => $c->type ?? null,
                        'nullable' => ($c->notnull ?? 0) === 0,
                        'default' => $c->dflt_value ?? null,
                        'pk' => ($c->pk ?? 0) === 1,
                    ];
                }

                $fks = DB::connection($connection)->select("PRAGMA foreign_key_list(\"$table\")");
                foreach ($fks as $fk) {
                    $relatedTable = $fk->table;
                    $relationName = Str::camel(str_replace('_id', '', $fk->from));
                    $model = Str::studly(Str::singular($relatedTable));

                    $structures[$table]['relationships']['belongs_to'][] = [
                        'relation' => $relationName,
                        'model' => $model,
                        'table' => $relatedTable,
                        'foreign_key' => $fk->from,
                        'owner_key' => $fk->to,
                    ];

                    $inverse = [
                        'relation' => Str::camel(Str::plural($table)),
                        'model' => Str::studly(Str::singular($table)),
                        'table' => $table,
                        'foreign_key' => $fk->from,
                        'local_key' => $fk->to,
                    ];

                    $structures[$relatedTable]['relationships']['has_many'][] = $inverse;
                }

                continue;
            }

            if ($driver === 'mysql') {
                $cols = DB::connection($connection)->select(
                    "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table",
                    ['table' => $table]
                );

                foreach ($cols as $col) {
                    $structures[$table]['columns'][$col->COLUMN_NAME] = [
                        'type' => $this->inferColumnType(explode('(', $col->COLUMN_TYPE)[0]),
                        'raw_type' => $col->COLUMN_TYPE,
                        'nullable' => $col->IS_NULLABLE === 'YES',
                        'default' => $col->COLUMN_DEFAULT,
                    ];
                }

                $fks = DB::connection($connection)->select(
                    "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table
                   AND REFERENCED_TABLE_NAME IS NOT NULL",
                    ['table' => $table]
                );

                foreach ($fks as $fk) {
                    $relationName = Str::camel(str_replace('_id', '', $fk->COLUMN_NAME));
                    $model = Str::studly(Str::singular($fk->REFERENCED_TABLE_NAME));

                    $structures[$table]['relationships']['belongs_to'][] = [
                        'relation' => $relationName,
                        'model' => $model,
                        'table' => $fk->REFERENCED_TABLE_NAME,
                        'foreign_key' => $fk->COLUMN_NAME,
                        'owner_key' => $fk->REFERENCED_COLUMN_NAME,
                    ];

                    $structures[$fk->REFERENCED_TABLE_NAME]['relationships']['has_many'][] = [
                        'relation' => Str::camel(Str::plural($table)),
                        'model' => Str::studly(Str::singular($table)),
                        'table' => $table,
                        'foreign_key' => $fk->COLUMN_NAME,
                        'local_key' => $fk->REFERENCED_COLUMN_NAME,
                    ];
                }

                continue;
            }

            if ($driver === 'pgsql') {
                $cols = DB::connection($connection)->select(
                    "SELECT column_name, data_type, is_nullable, column_default
                 FROM information_schema.columns
                 WHERE table_schema = 'public' AND table_name = :table",
                    ['table' => $table]
                );

                foreach ($cols as $col) {
                    $structures[$table]['columns'][$col->column_name] = [
                        'type' => $this->inferColumnType($col->data_type),
                        'raw_type' => $col->data_type,
                        'nullable' => $col->is_nullable === 'YES',
                        'default' => $col->column_default,
                    ];
                }

                $fks = DB::connection($connection)->select(
                    "SELECT kcu.column_name, ccu.table_name, ccu.column_name AS ref_column
                 FROM information_schema.table_constraints tc
                 JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                 JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
                 WHERE tc.constraint_type = 'FOREIGN KEY'
                   AND tc.table_name = :table",
                    ['table' => $table]
                );

                foreach ($fks as $fk) {
                    $relationName = Str::camel(str_replace('_id', '', $fk->column_name));
                    $model = Str::studly(Str::singular($fk->table_name));

                    $structures[$table]['relationships']['belongs_to'][] = [
                        'relation' => $relationName,
                        'model' => $model,
                        'table' => $fk->table_name,
                        'foreign_key' => $fk->column_name,
                        'owner_key' => $fk->ref_column,
                    ];

                    $structures[$fk->table_name]['relationships']['has_many'][] = [
                        'relation' => Str::camel(Str::plural($table)),
                        'model' => Str::studly(Str::singular($table)),
                        'table' => $table,
                        'foreign_key' => $fk->column_name,
                        'local_key' => $fk->ref_column,
                    ];
                }
            }
        }

        return $structures;
    }
    protected function getRelationshipsPresentOnModel(Model $model): array
    {
        $reflection = new ReflectionClass($model);
        $relations = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getNumberOfParameters() !== 0) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            try {
                $return = $method->invoke($model);

                if ($return instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                    $relations[] = [
                        'name' => $method->getName(),
                        'type' => class_basename($return),
                        'related' => get_class($return->getRelated()),
                    ];
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $relations;
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
