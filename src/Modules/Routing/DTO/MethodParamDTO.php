<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Routing\DTO;

//+name:        "request"
//+hasType:     true
//+isOptional:  false
//+type:        "Illuminate\Http\Request"
//+default:     null
class MethodParamDTO
{
    public function __construct(
        public string $name,
        public bool $hasType,
        public bool $isOptional,
        public ?string $type,
        public mixed $default,
    ) {}
}
