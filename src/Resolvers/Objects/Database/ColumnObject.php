<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Database;

class ColumnObject
{
    public function __construct(
        public string $name,
        public string $type,
        public string $rawType,
        public bool $nullable,
        public mixed $default,
    ) {}
}
