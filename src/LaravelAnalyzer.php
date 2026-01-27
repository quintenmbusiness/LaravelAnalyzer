<?php

namespace quintenmbusiness\LaravelAnalyzer;

use quintenmbusiness\LaravelAnalyzer\Modules\Routing\RoutingModule;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DatabaseModule;

class LaravelAnalyzer
{
    public DatabaseModule $databaseResolver;
    public RoutingModule $controllerResolver;

    public function __construct() {
        $this->databaseResolver = new DatabaseModule();
        $this->controllerResolver = new RoutingModule();
    }
}
