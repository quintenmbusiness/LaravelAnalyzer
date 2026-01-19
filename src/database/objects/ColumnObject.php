<?php

namespace quintenmbusiness\LaravelAnalyzer\database\objects;

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
