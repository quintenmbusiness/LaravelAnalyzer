<?php

use Illuminate\Support\Facades\Route;
use quintenmbusiness\LaravelAnalyzer\Laravel\Http\Controllers\Translations\InlineTranslationController;
use quintenmbusiness\LaravelAnalyzer\Laravel\Http\Controllers\Translations\TranslationEditorController;

Route::middleware(['web'])
    ->prefix('laravel-analyzer/translations')
    ->group(function () {
        Route::get('/', [TranslationEditorController::class, 'index'])->name('la.translations.index');
        Route::post('/', [TranslationEditorController::class, 'store'])->name('la.translations.store');
        Route::post('inline-save', [InlineTranslationController::class, 'store'])->name('la.translations.inline.save');

        Route::post('/translations/inline/fetch', [InlineTranslationController::class, 'fetch'])
            ->name('la.translations.inline.fetch');

    });
