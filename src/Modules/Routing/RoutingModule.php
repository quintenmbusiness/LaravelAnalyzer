<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Routing;

use Illuminate\Support\Str;
use quintenmbusiness\LaravelAnalyzer\Modules\Routing;
use quintenmbusiness\LaravelAnalyzer\Modules\Routing\DTO\ControllerMethodDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Routing\DTO\ControllersDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Routing\DTO\MethodParamDTO;
use ReflectionMethod;

class RoutingModule
{
    public function getControllers(): ControllersDTO
    {
        $collection = app('router')->getRoutes();
        $routesArray = $this->routesToArray($collection);
        $controllers = new ControllersDTO();

        foreach ($routesArray as $route) {

            $uri = $route->uri();
            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            $name = $route->getName();
            $actionName = $route->getActionName();
            $action = $route->getAction();
            $prefix = $route->getPrefix();
            $wheres = property_exists($route, 'wheres') ? $route->wheres : ($action['wheres'] ?? []);
            $middleware = method_exists($route, 'gatherMiddleware') ? $route->gatherMiddleware() : ($action['middleware'] ?? []);
            $defaults = $action['defaults'] ?? [];

            $parameters = collect();

            $parameterPlaceholders = $this->extractParameterPlaceholders($uri);

            foreach ($parameterPlaceholders as $p) {
                 $paramName = rtrim($p, '?');
                 $optional = str_ends_with($p, '?');
                 $pattern = $wheres[$paramName] ?? null;
                 $default = $defaults[$paramName] ?? null;

                 $parameters->push(new Routing\DTO\RouteParamDTO(
                     name: $paramName,
                     isOptional: $optional,
                     patern: $pattern,
                     default: $default
                 ));
            }

            $controllerClass = null;

            $controllerParameters = collect();

            if ($actionName && $actionName !== 'Closure' && Str::contains($actionName, '@')) {
                [$controllerClass, $controllerMethod] = explode('@', $actionName);
                if (class_exists($controllerClass) && method_exists($controllerClass, $controllerMethod)) {
                    $refMethod = new ReflectionMethod($controllerClass, $controllerMethod);

                    foreach ($refMethod->getParameters() as $param) {
                        $controllerParameters->add(new MethodParamDTO(
                            name: $param->getName(),
                            hasType: $param->hasType(),
                            isOptional: $param->isOptional(),
                            type: $param->hasType() ? (string)$param->getType() : null,
                            default: $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                        ));

                    }
                }
            }

            if(!$controllerClass) {
                //routes without a controller class
                //todo: find a way to include these in a meaningfull way
                continue;
            }

            $controller = $controllers->addController($controllerClass);

            $controllerMethod = new ControllerMethodDTO(
                methods: $methods,
                methodParameters: $controllerParameters,
                action: $name,
                actionName: $actionName,
                prefix: $prefix,
                url: $uri,
                routeParameters: $parameters,
                middleware: collect($middleware)
            );

            $controller->methods->add($controllerMethod);
            $controllers->updateController($controller);
        }

       return $controllers;
    }

    protected function routesToArray($collection): array
    {
        if (method_exists($collection, 'getRoutes')) {
            return $collection->getRoutes();
        }

        if ($collection instanceof \IteratorAggregate) {
            $out = [];
            foreach ($collection as $r) {
                $out[] = $r;
            }
            return $out;
        }

        return (array) $collection;
    }

    protected function extractParameterPlaceholders(string $uri): array
    {
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);
        return $matches[1] ?? [];
    }
}
