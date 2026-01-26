<?php

namespace quintenmbusiness\LaravelAnalyzer;

use quintenmbusiness\LaravelAnalyzer\Modules\Routing\DTO\ControllersDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\DatabaseDTO;

class LaravelApplicationObject
{
    public function __construct(
        public DatabaseDTO    $database,
        public ControllersDTO $controllers,
    ) {}
}