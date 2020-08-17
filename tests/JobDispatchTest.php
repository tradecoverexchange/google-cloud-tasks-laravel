<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\Task;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as Orchestra;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTaskServiceProvider;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\CloudTaskClientFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\Tests\Dummy\JobDummy;

class JobDispatchTest extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->mock(CloudTaskClientFactory::class, function (MockInterface $factory) {
            $client = \Mockery::mock(CloudTasksClient::class);

            $client->shouldReceive('createTask')
                ->with(
                    'projects/test/locations/europe-west1/queues/default',
                    \Mockery::type(Task::class)
                )
                ->once();

            $factory->shouldReceive('make')
                ->withAnyArgs()
                ->once()
                ->andReturn($client);
        });

        $this->withFactories(__DIR__.'/database/factories');
    }

    public function testCanDispatchToHttpQueue()
    {
        dispatch(new JobDummy())
            ->onConnection('http_cloud_tasks');
    }

    public function testCanDispatchToAppEngineQueue()
    {
        dispatch(new JobDummy())
            ->onConnection('app_engine_tasks');
    }

    public function testCanDispatchWithDelay()
    {
        dispatch(new JobDummy())
            ->onConnection('app_engine_tasks')
            ->delay(3000);
    }

    protected function getPackageProviders($app)
    {
        return [
            CloudTaskServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue', require __DIR__ . '/../config/queue.php');
        $app['config']->set('queue.connections.http_cloud_tasks.project_id', 'test');
        $app['config']->set('queue.connections.http_cloud_tasks.location', 'europe-west1');

        $app['config']->set('queue.connections.app_engine_tasks.project_id', 'test');
        $app['config']->set('queue.connections.app_engine_tasks.location', 'europe-west1');
    }
}
