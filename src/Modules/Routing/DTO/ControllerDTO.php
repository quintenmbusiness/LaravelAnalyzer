<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Routing\DTO;

use Illuminate\Support\Collection;

class ControllerDTO
{
    public string $name;
    public Collection $methods;

    public function __construct(
        public string $path,
    )
    {
        $this->methods = new Collection();
        $this->name = basename($this->path);
    }
}