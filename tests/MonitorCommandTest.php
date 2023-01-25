<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\Queue;
use Google\Cloud\Tasks\V2beta3\QueueStats;
use Illuminate\Support\Facades\App;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTaskServiceProvider;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\CloudTaskClientFactory;

class MonitorCommandTest extends TestCase
{
    protected MockInterface|CloudTasksClient $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->mock(CloudTaskClientFactory::class, function (MockInterface $factory) {
            $this->client = \Mockery::mock(CloudTasksClient::class);

            $factory->shouldReceive('make')
                ->withAnyArgs()
                ->once()
                ->andReturn($this->client);
        });
    }

    public function testQueueWorksWithMonitorCommand()
    {
        $stats = new QueueStats();
        $cloudQueue = new Queue();

        $this->client
            ->shouldReceive('getQueue')
            ->with(
                'projects/test/locations/europe-west1/queues/default',
                \Mockery::on(fn ($value) => is_array($value))
            )
            ->andReturn($cloudQueue);

        $cloudQueue->setStats($stats);
        $stats->setTasksCount(10);


        if (version_compare(App::version(), '9.21.0', '>=')) {
            $this->artisan('queue:monitor', [
                'queues' => 'http_cloud_tasks:default',
            ])
                ->expectsOutputToContain('default')
                ->assertSuccessful();
        } else {
            $this->artisan('queue:monitor', [
                'queues' => 'http_cloud_tasks:default',
            ])
                ->expectsTable(['Connection', 'Queue', 'Size', 'Status'], [[
                    'http_cloud_tasks', 'default', '10', 'OK',
                ]])
                ->assertExitCode(0);
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            CloudTaskServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.url', 'https://test.tradecoverexchange.com/');

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('queue', require __DIR__ . '/../config/queue.php');
        $app['config']->set('queue.connections.http_cloud_tasks.project_id', 'test');
        $app['config']->set('queue.connections.http_cloud_tasks.location', 'europe-west1');

        $app['config']->set('queue.failed.database', 'sqlite');

        include_once __DIR__.'/database/migrations/create_failed_jobs_table.php';
        (new \CreateFailedJobsTable())->up();
    }
}
