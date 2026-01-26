<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Database;

use quintenmbusiness\LaravelAnalyzer\Enums\ModelRelationshipType;

class ModelRelationObject
{
    public function __construct(
        public ModelRelationshipType $type,
        public string $relationName,
        public string|null $relatedModel,
        public string $relatedTable,
        public string $foreignKey,
        public string $localKey,
        public ?string $pivotTable = null,
        public ?string $morphName = null,
        public bool $nullable = false
    ) {}
}
