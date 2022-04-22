<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\Task;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as Orchestra;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTaskServiceProvider;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\CloudTaskClientFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\Tests\Dummy\JobDummy;

class JobDispatchTest extends Orchestra
{
    protected $client;

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

        $this->withFactories(__DIR__.'/database/factories');
    }

    public function testCanDispatchToHttpQueue()
    {
        $this->client->shouldReceive('createTask')
            ->withArgs(function (string $project, Task $task) {
                $this->assertSame('projects/test/locations/europe-west1/queues/default', $project);
                $this->assertInstanceOf(Task::class, $task);
                $this->assertSame(
                    'https://localhost/_googleTasks/http_cloud_tasks/default',
                    $task->getHttpRequest()->getUrl()
                );

                return true;
            })
            ->once();

        dispatch(new JobDummy())
            ->onConnection('http_cloud_tasks');
    }

    public function testCanDispatchToHttpQueueToConfiguredDomain()
    {
        Config::set('queue.connections.http_cloud_tasks.domain', 'test.tradecoverexchange.com');

        $this->client->shouldReceive('createTask')
            ->withArgs(function (string $project, Task $task) {
                $this->assertSame('projects/test/locations/europe-west1/queues/default', $project);
                $this->assertInstanceOf(Task::class, $task);
                $this->assertSame(
                    'https://test.tradecoverexchange.com/_googleTasks/http_cloud_tasks/default',
                    $task->getHttpRequest()->getUrl()
                );

                return true;
            })
            ->once();

        dispatch(new JobDummy())
            ->onConnection('http_cloud_tasks');
    }

    public function testCanDispatchToAppEngineQueue()
    {
        $this->client->shouldReceive('createTask')
            ->with(
                'projects/test/locations/europe-west1/queues/default',
                \Mockery::type(Task::class)
            )
            ->once();

        dispatch(new JobDummy())
            ->onConnection('app_engine_tasks');
    }

    public function testCanDispatchWithDelay()
    {
        $this->client->shouldReceive('createTask')
            ->with(
                'projects/test/locations/europe-west1/queues/default',
                \Mockery::type(Task::class)
            )
            ->once();

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
