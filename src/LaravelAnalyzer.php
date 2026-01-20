<?php

namespace quintenmbusiness\LaravelAnalyzer;

use quintenmbusiness\LaravelAnalyzer\database\ModelResolver;
use quintenmbusiness\LaravelAnalyzer\Routes\ControllerResolver;

class LaravelAnalyzer
{
    public ModelResolver $modelResolver;
    public ControllerResolver $controllerResolver;

    public function __construct() {
        $this->modelResolver = new ModelResolver();
        $this->controllerResolver = new ControllerResolver();
    }
}
