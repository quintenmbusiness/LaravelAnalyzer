<?php

namespace quintenmbusiness\LaravelAnalyzer\Controllers\objects;

class RouteParamObject
{
    public function __construct(
        public string $name,
        public bool $isOptional,
        public mixed $patern,
        public mixed $default,
    ) {}
}
