<?php

use Illuminate\Support\Facades\Route;
use quintenmbusiness\LaravelAnalyzer\Translations\TranslationEditorController;

Route::middleware(['web'])
    ->prefix('laravel-analyzer/translations')
    ->group(function () {
        Route::get('/', [TranslationEditorController::class, 'index'])->name('la.translations.index');
        Route::post('/', [TranslationEditorController::class, 'store'])->name('la.translations.store');
    });
