<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as Orchestra;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTaskServiceProvider;
use TradeCoverExchange\GoogleCloudTaskLaravel\Events\TaskFinished;
use TradeCoverExchange\GoogleCloudTaskLaravel\Events\TaskStarted;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\CloudTaskClientFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\GoogleCloudTasks;
use TradeCoverExchange\GoogleCloudTaskLaravel\Tests\Dummy\JobDummy;

class AppEngineJobProcessingTest extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
        $this->mock(CloudTaskClientFactory::class, function (MockInterface $factory) {
            $client = \Mockery::mock(CloudTasksClient::class);

            $factory->shouldReceive('make')
                ->withAnyArgs()
                ->once()
                ->andReturn($client);
        });
    }

    public function testProcessesJob()
    {
        $body = $this->makePayload(JobDummy::make());

        $this
            ->withoutExceptionHandling()
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 2)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks']
                ),
                $body
            )
            ->assertOk();
    }

    public function testFiresTaskStartedAndTaskFinishedEvents()
    {
        Event::fake([TaskStarted::class, TaskFinished::class]);

        $body = $this->makePayload(JobDummy::make());

        $this
            ->withoutExceptionHandling()
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 2)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks']
                ),
                $body
            )
            ->assertOk();

        Event::assertDispatched(TaskStarted::class, function (TaskStarted $event) {
            $this->assertInstanceOf(CloudTask::class, $event->task);

            return true;
        });

        Event::assertDispatched(TaskFinished::class, function (TaskFinished $event) {
            $this->assertInstanceOf(CloudTask::class, $event->task);
            $this->assertInstanceOf(Response::class, $event->response);
            $this->assertIsString($event->result);
            $this->assertSame($event->result, GoogleCloudTasks::STATUS_PROCESSED);

            return true;
        });
    }

    public function testTellsGoogleCloudTheTaskFailedFromTooManyTries()
    {
        $body = $this->makePayload(JobDummy::make()->mockExceptionFiring());

        $this
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 3)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks']
                ),
                $body
            )
            ->assertStatus(Response::HTTP_ALREADY_REPORTED);

        $this->assertDatabaseHas('failed_jobs', [
            'payload->displayName' => JobDummy::class,
            'queue' => 'default',
            'connection' => 'app_engine_tasks',
        ]);
    }

    public function testTellsGoogleCloudTheTaskFailedFromMarkingAsFailed()
    {
        $body = $this->makePayload(JobDummy::make()->mockFailing());

        $this
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 0)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks']
                ),
                $body
            )
            ->assertStatus(Response::HTTP_ALREADY_REPORTED);

        $this->assertDatabaseHas('failed_jobs', [
            'payload->displayName' => JobDummy::class,
            'queue' => 'default',
            'connection' => 'app_engine_tasks',
        ]);
    }

    public function testStoreFailedJobReport()
    {
        $body = $this->makePayload(JobDummy::make()->mockExceptionFiring());

        $this
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 1)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks']
                ),
                $body
            )
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    protected function makePayload($job)
    {
        $payload = [
            'displayName' => get_class($job),
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries' => $job->tries ?? null,
            'delay' => 0,
            'timeout' => $job->timeout ?? null,
            'timeoutAt' => null,
            'data' => [
                'commandName' => $job,
                'command' => $job,
            ],
        ];

        return array_merge($payload, [
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize(clone $job),
            ],
            'id' => '123',
            'attempts' => 0,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            CloudTaskServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('queue', require __DIR__ . '/../config/queue.php');
        $app['config']->set('queue.connections.app_engine_tasks.project_id', 'test');
        $app['config']->set('queue.connections.app_engine_tasks.location', 'europe-west1');
        $app['config']->set('queue.connections.app_engine_tasks.project_id', 'test');
        $app['config']->set('queue.connections.app_engine_tasks.location', 'europe-west1');

        $app['config']->set('queue.failed.database', 'sqlite');

        include_once __DIR__.'/database/migrations/create_failed_jobs_table.php';
        (new \CreateFailedJobsTable())->up();
    }
}
