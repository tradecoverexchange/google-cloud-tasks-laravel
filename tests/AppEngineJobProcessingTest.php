<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as Orchestra;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTaskServiceProvider;
use TradeCoverExchange\GoogleCloudTaskLaravel\Dispatchers\AppEngineDispatcher;
use TradeCoverExchange\GoogleCloudTaskLaravel\Events\TaskFinished;
use TradeCoverExchange\GoogleCloudTaskLaravel\Events\TaskStarted;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\CloudTaskClientFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\DispatcherFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\GoogleCloudTasks;
use TradeCoverExchange\GoogleCloudTaskLaravel\Tests\Dummy\JobDummy;
use TradeCoverExchange\GoogleCloudTaskLaravel\Tests\Dummy\JobUrlGeneration;

class AppEngineJobProcessingTest extends Orchestra
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function test_processes_job()
    {
        $this->configureClient();

        $body = $this->makePayload(JobDummy::make());

        $this
            ->withoutExceptionHandling()
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 2)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks', 'queue' => 'default']
                ),
                $body
            )
            ->assertNoContent();
    }

    public function test_processes_job_that_are_released_provides_the_correct_status()
    {
        $body = $this->makePayload(JobDummy::make()->mockRelease(), 2);

        $this->mock(DispatcherFactory::class, function (MockInterface $factory) {
            $dispatcher = \Mockery::mock(AppEngineDispatcher::class);
            $dispatcher->shouldReceive('dispatch')
                ->withArgs(function (
                    $id,
                    $queue,
                    $payload,
                    $scheduledFor,
                    $cloudQueue
                ) {
                    $this->assertIsString($id);
                    $this->assertSame('app_engine_tasks', $queue);
                    $this->assertSame('default', $cloudQueue);
                    $this->assertJson($payload);
                    $json = json_decode($payload, true);
                    $this->assertSame(JobDummy::class, data_get($json, 'displayName'));
                    $this->assertSame(3, data_get($json, 'attempts'));

                    return true;
                })
                ->once();
            $factory->shouldReceive('makeAppEngineDispatcher')->once()->andReturn($dispatcher);
        });

        $this
            ->withoutExceptionHandling()
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 2)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks', 'queue' => 'default']
                ),
                $body
            )
            ->assertStatus(Response::HTTP_PARTIAL_CONTENT);
    }

    public function test_url_middleware_allows_for_url_generation()
    {
        $this->configureClient();

        $body = $this->makePayload(JobUrlGeneration::make());

        Cache::forget('test-url');

        $this
            ->withoutExceptionHandling()
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 2)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks', 'queue' => 'default']
                ),
                $body
            )
            ->assertNoContent();

        $this->assertSame('https://test.tradecoverexchange.com/test', Cache::get('test-url'));
    }

    public function test_fires_task_started_and_task_finished_events()
    {
        $this->configureClient();

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
                    ['connection' => 'app_engine_tasks', 'queue' => 'default']
                ),
                $body
            )
            ->assertNoContent();

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

    public function test_releases_the_job_back_onto_the_queue_after_throwing_an_exception_in_the_job()
    {
        $body = $this->makePayload(JobDummy::make()->mockExceptionFiring(), 1);

        $this->mock(DispatcherFactory::class, function (MockInterface $factory) {
            $dispatcher = \Mockery::mock(AppEngineDispatcher::class);
            $dispatcher->shouldReceive('dispatch')
                ->withArgs(function (
                    $id,
                    $queue,
                    $payload,
                    $scheduledFor,
                    $cloudQueue
                ) {
                    $this->assertIsString($id);
                    $this->assertSame('app_engine_tasks', $queue);
                    $this->assertSame('default', $cloudQueue);
                    $this->assertJson($payload);
                    $json = json_decode($payload, true);
                    $this->assertSame(JobDummy::class, data_get($json, 'displayName'));
                    $this->assertSame(2, data_get($json, 'attempts'));

                    return true;
                })
                ->once();
            $factory->shouldReceive('makeAppEngineDispatcher')->once()->andReturn($dispatcher);
        });

        $this
            ->withoutExceptionHandling()
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 0)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks', 'queue' => 'default']
                ),
                $body
            )
            ->assertStatus(Response::HTTP_RESET_CONTENT);
    }

    public function test_releases_the_jobs_with_delay()
    {
        Carbon::setTestNow($timestamp = now());
        $body = $this->makePayload(
            JobDummy::make()
                ->mockExceptionFiring()
                ->withBackoff([5,10,20,30])
                ->withTries(10),
            3,
        );

        $this->mock(DispatcherFactory::class, function (MockInterface $factory) use ($timestamp) {
            $dispatcher = \Mockery::mock(AppEngineDispatcher::class);
            $dispatcher->shouldReceive('dispatch')
                ->withArgs(function (
                    $id,
                    $queue,
                    $payload,
                    $scheduledFor,
                    $cloudQueue
                ) use ($timestamp) {
                    $this->assertIsString($id);
                    $this->assertSame('app_engine_tasks', $queue);
                    $this->assertSame('default', $cloudQueue);
                    $this->assertJson($payload);
                    $json = json_decode($payload, true);
                    $this->assertSame(JobDummy::class, data_get($json, 'displayName'));
                    $this->assertSame(4, data_get($json, 'attempts'));
                    $this->assertNotNull($scheduledFor);
                    $this->assertIsNumeric($scheduledFor);
                    $this->assertSame($timestamp->timestamp + 30, $scheduledFor);

                    return true;
                })
                ->once();
            $factory->shouldReceive('makeAppEngineDispatcher')->once()->andReturn($dispatcher);
        });

        $this
            ->withoutExceptionHandling()
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 0)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks', 'queue' => 'default']
                ),
                $body
            )
            ->assertStatus(Response::HTTP_RESET_CONTENT);
    }

    public function test_tells_google_cloud_the_task_failed_from_too_many_tries()
    {
        $this->configureClient();

        $body = $this->makePayload(JobDummy::make()->mockExceptionFiring(), 3);

        $this
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 0)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks', 'queue' => 'default']
                ),
                $body
            )
            ->assertStatus(Response::HTTP_ALREADY_REPORTED);

        $this->assertDatabaseHas('failed_jobs', [
            'payload->displayName' => JobDummy::class,
            'queue' => 'default',
            'connection' => 'app_engine_tasks',
        ]);

        $this->assertStringStartsWith(
            'Illuminate\Queue\MaxAttemptsExceededException: TradeCoverExchange\GoogleCloudTaskLaravel\Tests\Dummy\JobDummy has been attempted too many times or run too long. The job may have previously timed out.',
            DB::table('failed_jobs')->soleValue('exception')
        );
    }

    public function test_tells_google_cloud_the_task_failed_from_marking_as_failed()
    {
        $this->configureClient();

        $body = $this->makePayload(JobDummy::make()->mockFailing());

        $this
            ->withHeader('X-AppEngine-TaskName', '123')
            ->withHeader('X-AppEngine-QueueName', 'default')
            ->withHeader('X-AppEngine-TaskExecutionCount', 0)
            ->postJson(
                route(
                    'google.tasks',
                    ['connection' => 'app_engine_tasks', 'queue' => 'default']
                ),
                $body
            )
            ->assertStatus(Response::HTTP_ALREADY_REPORTED);

        $this->assertDatabaseHas('failed_jobs', [
            'payload->displayName' => JobDummy::class,
            'queue' => 'default',
            'connection' => 'app_engine_tasks',
        ]);

        $this->assertStringStartsWith(
            'RuntimeException: Marked as failure',
            DB::table('failed_jobs')->soleValue('exception')
        );
    }

    protected function makePayload($job, $attempt = 0)
    {
        return [
            'displayName' => get_class($job),
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries' => $job->tries ?? null,
            'delay' => $job->delay ?? null,
            'backoff' => method_exists($job, 'backoff') && $job->backoff()
                ? implode(',', $job->backoff())
                : null,
            'timeout' => $job->timeout ?? null,
            'timeoutAt' => null,
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize(clone $job),
            ],
            'id' => '123',
            'attempts' => $attempt,
        ];
    }

    protected function configureClient()
    {
        $this->mock(CloudTaskClientFactory::class, function (MockInterface $factory) {
            $client = \Mockery::mock(CloudTasksClient::class);

            $factory->shouldReceive('make')
                ->withAnyArgs()
                ->once()
                ->andReturn($client);
        });
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
        $app['config']->set('queue.connections.app_engine_tasks.project_id', 'test');
        $app['config']->set('queue.connections.app_engine_tasks.location', 'europe-west1');

        $app['config']->set('queue.failed.database', 'sqlite');

        include_once __DIR__.'/database/migrations/create_failed_jobs_table.php';
        (new \CreateFailedJobsTable())->up();
    }
}
