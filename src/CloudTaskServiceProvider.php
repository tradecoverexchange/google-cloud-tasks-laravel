<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use TradeCoverExchange\GoogleCloudTaskLaravel\Controllers\CloudTasksController;
use TradeCoverExchange\GoogleCloudTaskLaravel\Middlewares\CloudTasks;
use TradeCoverExchange\GoogleCloudTaskLaravel\Middlewares\ConfigureUrlGenerator;

class CloudTaskServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../config/queue.php' => config_path('queue.php'),
                ],
                'cloud-task-config'
            );

            $this->commands([
                Commands\CreateQueueCommand::class,
                Commands\DeleteQueueCommand::class,
                Commands\PurgeQueueCommand::class,
                Commands\QueueStatusCommand::class,
                Commands\QueueStatsCommand::class,
                Commands\UpdateQueueCommand::class,
                Commands\ListTasksCommand::class,
            ]);
        }

        $this->mapRoutes();
    }

    public function register(): void
    {
        $this->app->when(CloudTasksController::class)
            ->needs(Worker::class)
            ->give(function () {
                return $this->app->make('queue.worker');
            });

        $this->app->scoped(GoogleCloudTasks::class);

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
        Route::post('/_googleTasks/{connection}/{queue}', CloudTasksController::class)
            ->name('google.tasks')
            ->middleware([CloudTasks::class, ConfigureUrlGenerator::class]);
    }
}
