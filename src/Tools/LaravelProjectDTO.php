<?php

namespace quintenmbusiness\LaravelAnalyzer\Tools;

class LaravelProjectDTO
{
    public array $rootFiles = [];

    public array $app = [
        'Console' => [],
        'Exceptions' => [],
        'Http' => [
            'Controllers' => [],
            'Middleware' => [],
            'Requests' => [],
            'Resources' => [],
        ],
        'Models' => [],
        'Policies' => [],
        'Providers' => [],
        'Rules' => [],
        'Services' => [],
        'Repositories' => [],
        'Jobs' => [],
        'Events' => [],
        'Listeners' => [],
        'Mail' => [],
        'Notifications' => [],
        'Enums' => [],
        'DTO' => [],
        'Traits' => [],
        'Helpers' => [],
    ];

    public array $bootstrap = [];

    public array $config = [];

    public array $database = [
        'Factories' => [],
        'Seeders' => [],
        'Migrations' => [],
        'SeedData' => [],
    ];

    public array $lang = [];

    public array $public = [
        'Assets' => [],
        'Build' => [],
        'Uploads' => [],
    ];

    public array $resources = [
        'Views' => [],
        'Lang' => [],
        'JS' => [],
        'CSS' => [],
        'Sass' => [],
        'Images' => [],
        'Components' => [],
    ];

    public array $routes = [
        'web' => [],
        'api' => [],
        'console' => [],
        'channels' => [],
        'custom' => [],
    ];

    public array $storage = [
        'Framework' => [],
        'Logs' => [],
        'App' => [],
    ];

    public array $tests = [
        'Feature' => [],
        'Unit' => [],
        'Browser' => [],
        'Pest' => [],
        'Helpers' => [],
        'Fixtures' => [],
    ];

    public array $tools = [
        'Scripts' => [],
        'CI' => [],
        'Docker' => [],
        'Dev' => [],
    ];

    public array $docs = [];

    public array $excluded = [
        'vendor',
        'node_modules',
        '.git',
        '.idea',
        '.vscode',
    ];

    public function __construct(
        public string $basePath,
        array $rootFiles = [],
        array $app = [],
        array $bootstrap = [],
        array $config = [],
        array $database = [],
        array $lang = [],
        array $public = [],
        array $resources = [],
        array $routes = [],
        array $storage = [],
        array $tests = [],
        array $tools = [],
        array $docs = []
    ) {
        $this->rootFiles = $rootFiles;
        $this->app = array_replace_recursive($this->app, $app);
        $this->bootstrap = $bootstrap;
        $this->config = $config;
        $this->database = array_replace_recursive($this->database, $database);
        $this->lang = $lang;
        $this->public = array_replace_recursive($this->public, $public);
        $this->resources = array_replace_recursive($this->resources, $resources);
        $this->routes = array_replace_recursive($this->routes, $routes);
        $this->storage = array_replace_recursive($this->storage, $storage);
        $this->tests = array_replace_recursive($this->tests, $tests);
        $this->tools = array_replace_recursive($this->tools, $tools);
        $this->docs = $docs;
    }
}
