<?php

namespace quintenmbusiness\LaravelAnalyzer\Routes\objects;

use Illuminate\Database\Eloquent\Collection;

class ControllersObject
{
    public Collection $controllers;

    public function __construct()
    {
        $this->controllers = new Collection();
    }

    public function addController(string $path): ControllerObject
    {
        $existingController = $this->controllers->firstWhere('path', $path);

        if ($existingController) {
            return $existingController;
        }

        $controller = (new ControllerObject($path));

        $this->controllers->push(new ControllerObject($path));

        return $controller;
    }

    public function updateController(ControllerObject $controller): void
    {
        $index = $this->controllers
            ->search(fn ($item) => $item->path === $controller->path);
        if ($index === false) {
            return;
        }

        $this->controllers[$index] = $controller;
    }
}