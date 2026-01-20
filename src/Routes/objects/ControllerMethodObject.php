<?php

namespace quintenmbusiness\LaravelAnalyzer\Routes\objects;

use Illuminate\Support\Collection;

class ControllerMethodObject
{
    public function __construct(
        public array $methods,
        public Collection $methodParameters,
        public string|null $action,
        public string|null $actionName,
        public string|null $prefix,
        public string $url,
        public Collection $routeParameters,
        public Collection $middleware,
    ) {}
}