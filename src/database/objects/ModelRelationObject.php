<?php

namespace quintenmbusiness\LaravelAnalyzer\database\objects;

class ModelRelationObject
{
    public function __construct(
        public string $method,
        public string $returns,
        public string $relatedTable,
    ) {}
}
