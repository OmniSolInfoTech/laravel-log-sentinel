<?php

use Illuminate\Support\Facades\Route;
use Osit\LogSentinel\Http\Controllers\DashboardController;
use Osit\LogSentinel\Http\Controllers\LogsController;
use Osit\LogSentinel\Http\Controllers\SourcesController;
use Osit\LogSentinel\Http\Controllers\AlertsController;
use Osit\LogSentinel\Http\Controllers\SettingsController;
use Osit\LogSentinel\Http\Middleware\EnsureLogSentinelEnabled;

Route::group([
    'prefix' => config('log-sentinel.route_prefix'),
    'middleware' => array_merge(
        config('log-sentinel.middleware', ['web']),
        [EnsureLogSentinelEnabled::class]
    ),
], function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->name('log-sentinel.dashboard');

    Route::get('/logs', [LogsController::class, 'index'])
        ->name('log-sentinel.logs.index');

    Route::get('/logs/data', [LogsController::class, 'data'])
        ->name('log-sentinel.logs.data');

    Route::get('/logs/{entry}', [LogsController::class, 'show'])
        ->name('log-sentinel.logs.show');

    Route::get('/sources', [SourcesController::class, 'index'])
        ->name('log-sentinel.sources.index');

    Route::get('/sources/create', [SourcesController::class, 'create'])
        ->name('log-sentinel.sources.create');

    Route::post('/sources', [SourcesController::class, 'store'])
        ->name('log-sentinel.sources.store');

    Route::get('/sources/{source}/edit', [SourcesController::class, 'edit'])
        ->name('log-sentinel.sources.edit');

    Route::put('/sources/{source}', [SourcesController::class, 'update'])
        ->name('log-sentinel.sources.update');

    Route::delete('/sources/{source}', [SourcesController::class, 'destroy'])
        ->name('log-sentinel.sources.destroy');

    Route::post('/sources/{source}/toggle', [SourcesController::class, 'toggle'])
        ->name('log-sentinel.sources.toggle');

    Route::post('/sources/{source}/test', [SourcesController::class, 'test'])
        ->name('log-sentinel.sources.test');

    Route::post('/sources/{source}/scan', [SourcesController::class, 'scan'])
        ->name('log-sentinel.sources.scan');

    Route::get('/alerts', [AlertsController::class, 'index'])
        ->name('log-sentinel.alerts.index');

    Route::get('/alerts/data', [AlertsController::class, 'data'])
        ->name('log-sentinel.alerts.data');

    Route::get('/alerts/{alert}', [AlertsController::class, 'show'])
        ->name('log-sentinel.alerts.show');

    Route::post('/alerts/{alert}/acknowledge', [AlertsController::class, 'acknowledge'])
        ->name('log-sentinel.alerts.acknowledge');

    Route::post('/alerts/{alert}/resolve', [AlertsController::class, 'resolve'])
        ->name('log-sentinel.alerts.resolve');

    Route::post('/alerts/{alert}/reopen', [AlertsController::class, 'reopen'])
        ->name('log-sentinel.alerts.reopen');

    Route::get('/settings', [SettingsController::class, 'index'])
        ->name('log-sentinel.settings.index');
});
