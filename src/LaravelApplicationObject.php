<?php

namespace quintenmbusiness\LaravelAnalyzer;

use quintenmbusiness\LaravelAnalyzer\database\objects\DatabaseObject;
use quintenmbusiness\LaravelAnalyzer\Controllers\objects\ControllersObject;

class LaravelApplicationObject
{
    /**
     * @param DatabaseObject    $database
     * @param ControllersObject $controllers
     */
    public function __construct(
        public DatabaseObject    $database,
        public ControllersObject $controllers,
    )
    {
    }
}