<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Controllers;

class RouteParamObject
{
    public function __construct(
        public string $name,
        public bool $isOptional,
        public mixed $patern,
        public mixed $default,
    ) {}
}
