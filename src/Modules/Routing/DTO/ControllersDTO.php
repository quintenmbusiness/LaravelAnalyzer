<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Routing\DTO;

use Illuminate\Database\Eloquent\Collection;

class ControllersDTO
{
    public Collection $controllers;

    public function __construct()
    {
        $this->controllers = new Collection();
    }

    public function addController(string $path): ControllerDTO
    {
        $existingController = $this->controllers->firstWhere('path', $path);

        if ($existingController) {
            return $existingController;
        }

        $controller = (new ControllerDTO($path));

        $this->controllers->push(new ControllerDTO($path));

        return $controller;
    }

    public function updateController(ControllerDTO $controller): void
    {
        $index = $this->controllers
            ->search(fn ($item) => $item->path === $controller->path);
        if ($index === false) {
            return;
        }

        $this->controllers[$index] = $controller;
    }

    public function getControllerWithName(string $name): ControllerDTO
    {
        return $this->controllers->firstWhere('name', $name);
    }

    public function getControllerWithPath(string $path): ControllerDTO
    {
        return $this->controllers->firstWhere('path', $path);
    }
}