<?php

namespace quintenmbusiness\LaravelAnalyzer\Translations;

use Illuminate\Support\Collection;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use Illuminate\Support\Facades\File;
use quintenmbusiness\LaravelAnalyzer\Controllers\objects\ControllersObject;

class ControllerViewScanner
{
    public function getViewUsages(ControllersObject $controllers): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $viewUsages = [];

        foreach ($controllers->controllers as $controller) {
            $path = base_path($controller->path). '.php';

            if (!File::exists($path)) continue;



            $code = File::get($path);

            try {
                $ast = $parser->parse($code);
            } catch (\Throwable $e) {
                continue;
            }

            $traverser = new NodeTraverser();

            $visitor = new class($controller, $viewUsages) extends NodeVisitorAbstract {
                public $controller;
                public $viewUsages;

                public function __construct($controller, &$viewUsages)
                {
                    $this->controller = $controller;
                    $this->viewUsages = &$viewUsages;
                    $this->currentMethod = null;
                }

                public function enterNode(Node $node)
                {
                    if ($node instanceof Node\Stmt\ClassMethod) {
                        $this->currentMethod = $node->name->toString();
                    }


                    // function calls: view('blade.name', [...])
                    if ($node instanceof Node\Expr\FuncCall) {
                        if ($node->name instanceof Node\Name && $node->name->toString() === 'view') {

                            $viewName = $this->resolveStringArg($node->args[0]->value ?? null);
                            $params = $this->resolveParamsArg($node->args[1]->value ?? null);
                            if ($viewName) {
                                $this->viewUsages[] = [
                                    'controller' => $this->controller->name,
                                    'method' => $this->currentMethod,
                                    'view' => $viewName,
                                    'params' => $params,
                                ];
                            }
                        }
                    }

                    // static calls: View::make('blade.name', [...])
                    if ($node instanceof Node\Expr\StaticCall) {
                        $class = $node->class;
                        $method = $node->name instanceof Node\Identifier ? $node->name->toString() : null;
                        $className = $class instanceof Node\Name ? $class->toString() : null;

                        if (in_array($className, ['View', '\\View', 'Illuminate\\Support\\Facades\\View']) &&
                            in_array($method, ['make', 'render'])) {

                            $viewName = $this->resolveStringArg($node->args[0]->value ?? null);
                            $params = $this->resolveParamsArg($node->args[1]->value ?? null);

                            if ($viewName) {
                                $this->viewUsages[] = [
                                    'controller' => $this->controller->name,
                                    'method' => $this->currentMethod,
                                    'view' => $viewName,
                                    'params' => $params,
                                ];
                            }
                        }
                    }
                }

                private function resolveStringArg($node)
                {
                    return $node instanceof Node\Scalar\String_ ? $node->value : null;
                }

                private function resolveParamsArg($node)
                {
                    if ($node instanceof Node\Expr\Array_) {
                        $out = [];
                        foreach ($node->items as $item) {
                            $key = $item->key instanceof Node\Scalar\String_ ? $item->key->value : null;
                            $val = $item->value instanceof Node\Scalar\String_ ? $item->value->value : null;
                            $out[$key ?? ''] = $val ?? '';
                        }
                        return $out;
                    }
                    return [];
                }
            };

            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);
        }

        return $viewUsages;
    }
}
