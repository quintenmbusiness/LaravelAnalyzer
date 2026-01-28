<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO;

use Illuminate\Support\Collection;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationshipDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\Relationships\RelationThroughDTO;

class TableDTO
{
    public string $name;
    /**
     * @var Collection<ColumnDTO>
     */
    public Collection    $columns;
    /**
     * @var Collection<RelationshipDTO>
     */
    public Collection    $relations;
    /**
     * @var Collection<RelationThroughDTO>
     */
    public Collection $relationsThrough;

    /**
     * @param string $name
     */
    public function __construct(
        string $name,
        Collection    $columns,
        Collection    $relations,
    ) {
        $this->name = $name;
        $this->columns = $columns;
        $this->relations = $relations;
        $this->relationsThrough = new Collection();
    }
}
