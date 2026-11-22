<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

$legislatureRoutes = function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/iniciativas', [InitiativeController::class, 'index'])->name('initiatives.index');
    Route::get('/partidos', [PartyController::class, 'index'])->name('parties.index');
    Route::get('/partidos/{party}', [PartyController::class, 'show'])->name('parties.show')->where('party', '[a-zA-Z-]+');
};

Route::group([], $legislatureRoutes);

Route::prefix('/legislatura/{legislature}')
    ->name('legislatures.')
    ->where(['legislature' => '[IVXLCDMivxlcdm]+'])
    ->group($legislatureRoutes);

Route::get('/iniciativas/{initiative}', [InitiativeController::class, 'show'])->name('initiatives.show');

Route::view('/sobre', 'pages.about')->name('about');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
