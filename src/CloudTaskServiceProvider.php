<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use TradeCoverExchange\GoogleCloudTaskLaravel\Controllers\CloudTasksController;
use TradeCoverExchange\GoogleCloudTaskLaravel\Middlewares\CloudTasks;

class CloudTaskServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../config/queue.php' => config_path('queue.php'),
                ],
                'cloud-task-config'
            );
        }

        $queueManager = $this->app->make('queue');
        $queueManager->extend(Connectors\AppEngineConnector::DRIVER, function () {
            return $this->app->make(Connectors\AppEngineConnector::class);
        });
        $queueManager->extend(Connectors\CloudTasksConnector::DRIVER, function () {
            return $this->app->make(Connectors\CloudTasksConnector::class);
        });

        $this->mapRoutes();
    }

    public function register()
    {
        $this->app->when(CloudTasksController::class)
            ->needs(Worker::class)
            ->give(function () {
                return $this->app->make('queue.worker');
            });

        $this->app->singleton(GoogleCloudTasks::class);
    }

    protected function mapRoutes()
    {
        Route::any('/_/google-tasks/{connection}', CloudTasksController::class)
            ->name('google.tasks')
            ->middleware(CloudTasks::class);
    }
}
