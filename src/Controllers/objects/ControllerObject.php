<?php

namespace quintenmbusiness\LaravelAnalyzer\Controllers\objects;

use Illuminate\Support\Collection;

class ControllerObject
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