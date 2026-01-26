<?php

namespace quintenmbusiness\LaravelAnalyzer;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use quintenmbusiness\LaravelAnalyzer\Laravel\Http\Middleware\TranslationEditorMiddleware;

class LaravelAnalyzerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        $this->loadViewsFrom(
            __DIR__ . '/views',
            'laravel-analyzer'
        );


        Blade::directive('lang', function ($expression) {
            return "<?php echo '<!--__TRANS_START__' . {$expression} . '__TRANS_END__-->'; echo __({$expression}); ?>";
        });

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('translation.editor', TranslationEditorMiddleware::class);
    }
}
