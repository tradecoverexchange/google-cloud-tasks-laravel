<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTaskServiceProvider;

class ServiceProviderTest extends Orchestra
{
    public function testPublishesTheQueueConfig()
    {
        $this->artisan('vendor:publish', [
            '--provider' => 'TradeCoverExchange\GoogleCloudTaskLaravel\CloudTaskServiceProvider',
            '--tag' => 'cloud-task-config',
            '--force' => true,
        ])
            ->assertExitCode(0);

        $original = require __DIR__ . '/../config/queue.php';
        $published = require config_path('queue.php');

        $this->assertSame($original, $published);
    }

    protected function getPackageProviders($app)
    {
        return [
            CloudTaskServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
    }
}
