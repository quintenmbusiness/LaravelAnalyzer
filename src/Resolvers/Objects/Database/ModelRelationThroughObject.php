<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Database;

class ModelRelationThroughObject
{
    public function __construct(
        public \quintenmbusiness\LaravelAnalyzer\Enums\ModelRelationshipType $type,
        public string                                                        $relationName,
        public ?string                                                       $relatedModel,
        public string                                                        $relatedTable,
        public string                                                        $throughTable,
        public ?string                                                       $throughModel = null,
        public string                                                        $firstKey,
        public string                                                        $secondKey,
        public string                                                        $localKey,
        public string                                                        $secondLocalKey
    ) {}
}
