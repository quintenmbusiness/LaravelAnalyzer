<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO;

class ModelRelationThroughDTO
{
    public function __construct(
        public \quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType $type,
        public string                                                                        $relationName,
        public ?string                                                                       $relatedModel,
        public string                                                                        $relatedTable,
        public string                                                                        $throughTable,
        public ?string                                                                       $throughModel = null,
        public string                                                                        $firstKey,
        public string                                                                        $secondKey,
        public string                                                                        $localKey,
        public string                                                                        $secondLocalKey
    ) {}
}
