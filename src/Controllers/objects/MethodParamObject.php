<?php

namespace quintenmbusiness\LaravelAnalyzer\Controllers\objects;

//+name:        "request"
//+hasType:     true
//+isOptional:  false
//+type:        "Illuminate\Http\Request"
//+default:     null
class MethodParamObject
{
    public function __construct(
        public string $name,
        public bool $hasType,
        public bool $isOptional,
        public ?string $type,
        public mixed $default,
    ) {}
}
