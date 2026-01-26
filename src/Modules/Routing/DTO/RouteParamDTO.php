<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Routing\DTO;

class RouteParamDTO
{
    public function __construct(
        public string $name,
        public bool $isOptional,
        public mixed $patern,
        public mixed $default,
    ) {}
}
