<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO;

class ColumnDTO
{
    public function __construct(
        public string $name,
        public string $type,
        public string $rawType,
        public bool $nullable,
        public mixed $default,
    ) {}
}
