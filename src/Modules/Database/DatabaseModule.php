<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database;


use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\DatabaseDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\Services\DatabaseService;

class DatabaseModule
{
    public DatabaseService $databaseService;

    public function __construct() {
        $this->databaseService = new DatabaseService();
    }

    public function getDatabase(string|null $connection = null): DatabaseDTO
    {
        $database = new DatabaseDTO($connection);

        $this->databaseService->resolveInverseRelationships($database->tables);
        $this->databaseService->resolveThroughRelationships($database->tables);

        return $database;
    }
}
