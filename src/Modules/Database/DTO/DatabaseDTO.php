<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO;

use Illuminate\Support\Collection;

class DatabaseDTO
{
    /**
     * @var Collection<TableDTO>
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