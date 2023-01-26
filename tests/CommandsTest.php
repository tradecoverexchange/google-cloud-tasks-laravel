<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests;

use Google\ApiCore\PagedListResponse;
use Google\Cloud\Tasks\V2beta3\Attempt;
use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\Queue;
use Google\Cloud\Tasks\V2beta3\Queue\State;
use Google\Cloud\Tasks\V2beta3\QueueStats;
use Google\Cloud\Tasks\V2beta3\Task;
use Google\Protobuf\Timestamp;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTaskServiceProvider;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\CloudTaskClientFactory;

class CommandsTest extends TestCase
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

    public function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function test_creating_queue()
    {
        $this->client->shouldReceive('createQueue')
            ->with('projects/test/locations/europe-west1', \Mockery::on(function (Queue $queue) {
                return true;
            }))
            ->once();

        $this->artisan('google:cloud:queue:create', [
            'name' => 'http_cloud_tasks',
        ])
            ->assertExitCode(0);
    }

    public function test_deleting_queue()
    {
        $this->client->shouldReceive('deleteQueue')
            ->with('projects/test/locations/europe-west1/queues/default')
            ->once();

        $this->artisan('google:cloud:queue:delete', [
            'name' => 'http_cloud_tasks',
        ])
            ->expectsQuestion('This action will delete all unfinished tasks permanently, do you wish to continue?', 'y')
            ->assertExitCode(0);
    }

    public function test_deleting_queue_with_force()
    {
        $this->client->shouldReceive('deleteQueue')
            ->with('projects/test/locations/europe-west1/queues/default')
            ->once();

        $this->artisan('google:cloud:queue:delete', [
            'name' => 'http_cloud_tasks',
            '--force' => true,
        ])
            ->assertExitCode(0);
    }

    public function test_purging_queue()
    {
        $this->client->shouldReceive('purgeQueue')
            ->with('projects/test/locations/europe-west1/queues/default')
            ->once();

        $this->artisan('google:cloud:queue:clear', [
            'name' => 'http_cloud_tasks',
        ])
            ->expectsQuestion('This action will delete all unfinished tasks permanently, do you wish to continue?', 'y')
            ->assertExitCode(0);
    }

    public function test_purging_queue_with_force()
    {
        $this->client->shouldReceive('purgeQueue')
            ->with('projects/test/locations/europe-west1/queues/default')
            ->once();

        $this->artisan('google:cloud:queue:clear', [
            'name' => 'http_cloud_tasks',
            '--force' => true,
        ])
            ->assertExitCode(0);
    }

    public function test_update_queue()
    {
        $this->client->shouldReceive('updateQueue')
            ->with(\Mockery::on(function (Queue $queue) {
                return true;
            }))
            ->once();

        $this->artisan('google:cloud:queue:update', [
            'name' => 'http_cloud_tasks',
        ])
            ->assertExitCode(0);
    }

    public function test_providing_stats()
    {
        Carbon::setTestNow(now());

        $stats = new QueueStats();
        $cloudQueue = new Queue();

        $this->client
            ->shouldReceive('getQueue')
            ->with('projects/test/locations/europe-west1/queues/default', \Mockery::on(fn ($value) => is_array($value)))
            ->andReturn($cloudQueue);

        $cloudQueue->setState(Queue\State::RUNNING);
        $cloudQueue->setStats($stats);
        $stats->setTasksCount(10);
        $stats->setConcurrentDispatchesCount(20);
        $stats->setEffectiveExecutionRate(20);
        $stats->setOldestEstimatedArrivalTime((new Timestamp())->setSeconds(now()->timestamp));

        $this->artisan('google:cloud:queue:stats', [
            'name' => 'http_cloud_tasks',
        ])
            ->expectsTable([
                'queue',
                'state',
                'concurrent dispatches',
                'execution rate',
                'task count',
                'oldest arrival time',
            ], [[
                'http_cloud_tasks:default',
                State::name(State::RUNNING),
                20,
                20,
                10,
                now()->toDateTime()->format(\DateTime::ATOM),
            ]])
            ->assertExitCode(0);
    }

    public function test_provides_the_queue_status()
    {
        $cloudQueue = new Queue();

        $this->client
            ->shouldReceive('getQueue')
            ->with('projects/test/locations/europe-west1/queues/default')
            ->andReturn($cloudQueue);

        $this->client->shouldReceive('updateQueue')
            ->with(\Mockery::on(function (Queue $queue) {
                return true;
            }))
            ->never();

        $cloudQueue->setState(Queue\State::RUNNING);

        $this->artisan('google:cloud:queue:status', [
            'name' => 'http_cloud_tasks',
        ])
            ->expectsOutput('Queue http_cloud_tasks:default is running')
            ->assertExitCode(0);
    }

    public function test_toggles_the_queue_status()
    {
        $cloudQueue = new Queue();

        $this->client
            ->shouldReceive('getQueue')
            ->with('projects/test/locations/europe-west1/queues/default')
            ->andReturn($cloudQueue);

        $this->client->shouldReceive('updateQueue')
            ->with(\Mockery::on(function (Queue $queue) {
                return true;
            }))
            ->once();

        $cloudQueue->setState(Queue\State::RUNNING);

        $this->artisan('google:cloud:queue:status', [
            'name' => 'http_cloud_tasks',
            '--toggle' => true,
        ])
            ->expectsOutput('Queue http_cloud_tasks:default is running')
            ->expectsOutput('Queue http_cloud_tasks:default is now paused')
            ->assertExitCode(0);
    }

    public function test_retrieves_list_of_tasks()
    {
        Carbon::setTestNow(now());

        $tasks = [
            tap(new Task(), function (Task $task) {
                return $task->setName('first')
                    ->setFirstAttempt(
                        (new Attempt())
                            ->setDispatchTime((new Timestamp())->setSeconds(now()->timestamp))
                    )
                    ->setLastAttempt(
                        (new Attempt())
                            ->setDispatchTime((new Timestamp())->setSeconds(now()->timestamp))
                    )
                    ->setCreateTime((new Timestamp())->setSeconds(now()->timestamp))
                    ->setScheduleTime((new Timestamp())->setSeconds(now()->timestamp))
                    ->setDispatchCount(1);
            }),
            tap(new Task(), function (Task $task) {
                return $task->setName('second')
                    ->setFirstAttempt(
                        (new Attempt())
                            ->setDispatchTime((new Timestamp())->setSeconds(now()->timestamp))
                    )
                    ->setLastAttempt(
                        (new Attempt())
                            ->setDispatchTime((new Timestamp())->setSeconds(now()->timestamp))
                    )
                    ->setCreateTime((new Timestamp())->setSeconds(now()->timestamp))
                    ->setScheduleTime((new Timestamp())->setSeconds(now()->timestamp))
                    ->setDispatchCount(2);
            }),
        ];
        $paginated = \Mockery::mock(PagedListResponse::class)
            ->shouldReceive('iterateAllElements')
            ->andReturn(function () use ($tasks): \Generator {
                foreach ($tasks as $task) {
                    yield $task;
                }
            })
            ->getMock();

        $this->client
            ->shouldReceive('listTasks')
            ->with('projects/test/locations/europe-west1/queues/default')
            ->andReturn($paginated);

        $this->artisan('google:cloud:queue:tasks', [
            'name' => 'http_cloud_tasks',
        ])
            ->expectsTable([
                'name',
                'dispatch count',
                'last attempt at',
                'first attempt at',
                'created at',
                'scheduled at',
            ], [[
                'first',
                1,
                now()->toAtomString(),
                now()->toAtomString(),
                now()->toAtomString(),
                now()->toAtomString(),
            ], [
                'second',
                2,
                now()->toAtomString(),
                now()->toAtomString(),
                now()->toAtomString(),
                now()->toAtomString(),
            ]])
            ->assertExitCode(0);
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
