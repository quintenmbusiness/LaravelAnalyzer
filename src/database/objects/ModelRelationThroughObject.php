<?php

namespace quintenmbusiness\LaravelAnalyzer\database\objects;

use quintenmbusiness\LaravelAnalyzer\database\ModelRelationshipType;

class ModelRelationThroughObject
{
    public function __construct(
        public ModelRelationshipType $type,
        public string $relationName,
        public ?string $relatedModel,
        public string $relatedTable,
        public string $throughTable,
        public ?string $throughModel = null,
        public string $firstKey,
        public string $secondKey,
        public string $localKey,
        public string $secondLocalKey
    ) {}
}
