<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO;

use Illuminate\Support\Collection;

class TableDTO
{
    public function __construct(
        public string        $name,
        public ModelDTO|null $model,
        public Collection    $columns,
    ) {}
}
