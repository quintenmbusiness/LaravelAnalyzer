<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO;

class PrimaryKeyDTO
{
    public string $name;
    public string $type;
    public bool $autoIncrement;

    public function __construct(string $name, string $type, bool $autoIncrement = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->autoIncrement = $autoIncrement;
    }
}
