<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO;

use Illuminate\Support\Collection;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Repositories\Drivers\BaseDriver;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Services\DatabaseService;

class DatabaseDTO
{
    public Collection $tables;

    public BaseDriver $driver;

    public function __construct(public null|string $connection = null)
    {
        $this->connection = $connection ?? config('database.default');
        $this->driver = (new DatabaseService())->getDriver($this->connection);
        $this->tables = $this->driver->getTables();
    }
}
