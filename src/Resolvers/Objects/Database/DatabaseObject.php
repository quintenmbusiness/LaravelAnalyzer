<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Database;

use Illuminate\Support\Collection;

class DatabaseObject
{
    /**
     * @var Collection<TableObject>
     */
    public Collection $tables;

    public function __construct()
    {
        $this->tables = collect();
    }

    public function resolveModelRelationships(): void
    {
        foreach ($this->tables as $table) {
            foreach($table->model->relations as $modelRelationship) {
//                $relatedModel = $modelRelationship->relatedTable;
            }
        }
    }
}