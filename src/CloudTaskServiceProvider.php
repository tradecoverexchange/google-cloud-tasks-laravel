<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Queue\QueueManager;
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

        // Queue Factory extension used to make the package compatible with other packages
        // which might try to access the factory before the boot method of this service
        // provider has been called.
        $this->app->extend(QueueManager::class, function (QueueManager $queueManager) {
            $queueManager->extend(Connectors\AppEngineConnector::DRIVER, function () {
                return $this->app->make(Connectors\AppEngineConnector::class);
            });
            $queueManager->extend(Connectors\CloudTasksConnector::DRIVER, function () {
                return $this->app->make(Connectors\CloudTasksConnector::class);
            });

            return $queueManager;
        });
    }

    protected function mapRoutes()
    {
        Route::any('/_/google-tasks/{connection}', CloudTasksController::class)
            ->name('google.tasks')
            ->middleware(CloudTasks::class);
    }
}
