<?php

namespace quintenmbusiness\LaravelAnalyzer;

use quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Controllers\ControllersObject;
use quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Database\DatabaseObject;

class LaravelApplicationObject
{
    /**
     * @param \quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Database\DatabaseObject    $database
     * @param \quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Controllers\ControllersObject $controllers
     */
    public function __construct(
        public DatabaseObject    $database,
        public ControllersObject $controllers,
    )
    {
    }
}