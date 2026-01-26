<?php

namespace quintenmbusiness\LaravelAnalyzer;

use quintenmbusiness\LaravelAnalyzer\Resolvers\ControllerResolver;
use quintenmbusiness\LaravelAnalyzer\Resolvers\DatabaseResolver;

class LaravelAnalyzer
{
    public DatabaseResolver $databaseResolver;
    public ControllerResolver $controllerResolver;

    public function __construct() {
        $this->databaseResolver = new DatabaseResolver();
        $this->controllerResolver = new ControllerResolver();
    }

    public function getApplication(): LaravelApplicationObject
    {
        return new LaravelApplicationObject(
            $this->databaseResolver->getDatabase(),
            $this->controllerResolver->getControllers()
        );
    }
}
