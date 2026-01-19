<?php

namespace quintenmbusiness\LaravelAnalyzer\database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use quintenmbusiness\LaravelAnalyzer\database\objects\ColumnObject;
use quintenmbusiness\LaravelAnalyzer\database\objects\ModelObject;
use quintenmbusiness\LaravelAnalyzer\database\objects\ModelRelationObject;
use quintenmbusiness\LaravelAnalyzer\database\objects\TableObject;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class ModelResolver
{
    protected array $classMap;

    public function __construct(?array $classMap = null)
    {
        $this->classMap = $classMap ?? $this->loadComposerClassMap();
    }

    public function getModelClasses(): Collection
    {
        $models = collect();

        foreach ($this->discoverModelClasses() as $class) {
            try {
                $table = $this->inferTableNameFromModelClass($class);

                $columns = collect();

                foreach($this->getTableStructure($table)['columns'] as $name => $column) {
                    $columns->push(new ColumnObject(
                        $name,
                        $column['type'],
                        $column['raw_type'],
                        $column['nullable'],
                        $column['default'],
                    ));
                }

                $modelRelations = collect();

                foreach($this->getRelationshipsPresentOnModelIfInstantiable($class) as $relation) {
                    $modelRelations->push(new ModelRelationObject(
                        $relation['name'],
                        $relation['type'],
                        $relation['related'],
                    ));
                }

                $tableObject = new TableObject(
                    $table,
                    $columns,
                );

                $model = new ModelObject(
                    class_basename($class),
                    $class,
                    $tableObject,
                    $modelRelations,
                );

              $models->push($model);
            } catch (Throwable $e) {
                continue;
            }
        }

        return $models;
    }

    protected function discoverModelClasses(): array
    {
        $classes = [];

        foreach ($this->classMap as $class => $path) {
            if (!$this->isModelNamespace($class)) {
                continue;
            }

            if ($this->isEloquentModel($class)) {
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

    protected function inferTableNameFromModelClass(string $class): string
    {
        if (!class_exists($class)) {
            return Str::snake(Str::plural(class_basename($class)));
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
            $rows = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
            return array_map(fn($r) => $r->name, $rows);
        }

        if ($driver === 'mysql') {
            $rows = DB::connection($connection)->select("SELECT TABLE_NAME as name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'");
            return array_map(fn($r) => $r->name, $rows);
        }

        if ($driver === 'pgsql') {
            $rows = DB::connection($connection)->select("SELECT tablename as name FROM pg_tables WHERE schemaname = 'public'");
            return array_map(fn($r) => $r->name, $rows);
        }

        return Schema::connection($connection)->getConnection()->getSchemaBuilder()->getAllTables();
    }

    public function getTableStructure(string $table, string $connection = null): array
    {
        $connection = $connection ?? config('database.default');

        if (!Schema::connection($connection)->hasTable($table)) {
            return [];
        }

        $driver = DB::connection($connection)->getDriverName();

        if ($driver === 'sqlite') {
            $cols = DB::connection($connection)->select("PRAGMA table_info(\"$table\")");

            $columns = [];
            foreach ($cols as $c) {
                $columns[$c->name] = [
                    'type' => $this->inferColumnType(strtolower($c->type ?? '')),
                    'raw_type' => $c->type ?? null,
                    'nullable' => ($c->notnull ?? 0) === 0,
                    'default' => $c->dflt_value ?? null,
                    'pk' => ($c->pk ?? 0) === 1,
                ];
            }

            $foreignKeys = [];
            $fks = DB::connection($connection)->select("PRAGMA foreign_key_list(\"$table\")");
            foreach ($fks as $fk) {
                $foreignKeys[$fk->from] = [
                    'references_table' => $fk->table,
                    'references_column' => $fk->to,
                ];
            }

            $incoming = [];
            $tables = $this->getAllTables($connection);
            foreach ($tables as $t) {
                if ($t === $table) continue;
                $list = DB::connection($connection)->select("PRAGMA foreign_key_list(\"$t\")");
                foreach ($list as $fk) {
                    if ($fk->table === $table) {
                        $incoming[] = [
                            'from_table' => $t,
                            'from_column' => $fk->from,
                            'to_column' => $fk->to,
                        ];
                    }
                }
            }

            return [
                'columns' => $columns,
                'foreign_keys' => $foreignKeys,
                'incoming_relations' => $incoming,
            ];
        }

        if ($driver === 'mysql') {
            $cols = DB::connection($connection)->select(
                "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table",
                ['table' => $table]
            );

            $columns = [];
            foreach ($cols as $col) {
                $columns[$col->COLUMN_NAME] = [
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

            $foreignKeys = [];
            foreach ($fks as $fk) {
                $foreignKeys[$fk->COLUMN_NAME] = [
                    'references_table' => $fk->REFERENCED_TABLE_NAME,
                    'references_column' => $fk->REFERENCED_COLUMN_NAME,
                ];
            }

            $incoming = DB::connection($connection)->select(
                "SELECT TABLE_NAME, COLUMN_NAME
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND REFERENCED_TABLE_NAME = :table",
                ['table' => $table]
            );

            $incomingRelations = [];
            foreach ($incoming as $rel) {
                $incomingRelations[] = [
                    'from_table' => $rel->TABLE_NAME,
                    'from_column' => $rel->COLUMN_NAME,
                ];
            }

            return [
                'columns' => $columns,
                'foreign_keys' => $foreignKeys,
                'incoming_relations' => $incomingRelations,
            ];
        }

        if ($driver === 'pgsql') {
            $cols = DB::connection($connection)->select(
                "SELECT column_name, data_type, is_nullable, column_default
                 FROM information_schema.columns
                 WHERE table_schema = 'public' AND table_name = :table",
                ['table' => $table]
            );

            $columns = [];
            foreach ($cols as $col) {
                $columns[$col->column_name] = [
                    'type' => $this->inferColumnType($col->data_type),
                    'raw_type' => $col->data_type,
                    'nullable' => $col->is_nullable === 'YES',
                    'default' => $col->column_default,
                ];
            }

            $fks = DB::connection($connection)->select(
                "SELECT kcu.column_name, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name
                 FROM information_schema.table_constraints AS tc
                 JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
                 JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
                 WHERE constraint_type = 'FOREIGN KEY' AND tc.table_name = :table",
                ['table' => $table]
            );

            $foreignKeys = [];
            foreach ($fks as $fk) {
                $foreignKeys[$fk->column_name] = [
                    'references_table' => $fk->foreign_table_name,
                    'references_column' => $fk->foreign_column_name,
                ];
            }

            $incoming = DB::connection($connection)->select(
                "SELECT tc.table_name, kcu.column_name
                 FROM information_schema.table_constraints tc
                 JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                 JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
                 WHERE tc.constraint_type = 'FOREIGN KEY' AND ccu.table_name = :table",
                ['table' => $table]
            );

            $incomingRelations = [];
            foreach ($incoming as $rel) {
                $incomingRelations[] = [
                    'from_table' => $rel->table_name,
                    'from_column' => $rel->column_name,
                ];
            }

            return [
                'columns' => $columns,
                'foreign_keys' => $foreignKeys,
                'incoming_relations' => $incomingRelations,
            ];
        }

        $cols = Schema::connection($connection)->getColumnListing($table);

        $columns = [];
        foreach ($cols as $c) {
            $columns[$c] = [
                'type' => null,
                'raw_type' => null,
                'nullable' => null,
                'default' => null,
            ];
        }

        return [
            'columns' => $columns,
            'foreign_keys' => [],
            'incoming_relations' => [],
        ];
    }

    protected function getRelationshipsPresentOnModelIfInstantiable(string $class): array
    {
        try {
            if (!class_exists($class)) {
                return [];
            }

            $instance = new $class;

            if (!$instance instanceof Model) {
                return [];
            }

            return $this->getRelationshipsPresentOnModel($instance);
        } catch (Throwable) {
            return [];
        }
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
