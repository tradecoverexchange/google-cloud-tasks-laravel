<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Contracts\Events\Dispatcher as EventBus;
use Illuminate\Http\Response;
use Illuminate\Queue\Events;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class GoogleCloudTasks
{
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXCEPTION_THROWN = 'exception_thrown';

    /**
     * @var Response|null
     */
    protected $response = null;
    /**
     * @var FailedJobProviderInterface
     */
    private $failedJobProvider;
    /**
     * @var string|null
     */
    private $status = null;

    public function __construct(FailedJobProviderInterface $failedJobProvider)
    {
        $this->failedJobProvider = $failedJobProvider;
    }

    /**
     * @return null|Response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function subscribe(EventBus $dispatcher)
    {
        $dispatcher->listen(Events\JobFailed::class, function (Events\JobFailed $event) {
            $this->jobFailed($event);
        });

        $dispatcher->listen(Events\JobExceptionOccurred::class, function (Events\JobExceptionOccurred $event) {
            $this->jobExceptionOccurred($event);
        });

        $dispatcher->listen(Events\JobProcessed::class, function (Events\JobProcessed $event) {
            $this->jobProcessed($event);
        });
    }

    protected function jobFailed(Events\JobFailed $event)
    {
        $this->response = new Response($event->exception->getMessage(), Response::HTTP_ALREADY_REPORTED);
        $this->status = self::STATUS_FAILED;

        $this->failedJobProvider->log(
            $event->connectionName,
            $event->job->getQueue(),
            $event->job->getRawBody(),
            $event->exception
        );
    }

    protected function jobExceptionOccurred(Events\JobExceptionOccurred $event)
    {
        if ($this->response === null) {
            $this->status = self::STATUS_EXCEPTION_THROWN;
            $this->response = new Response($event->exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    protected function jobProcessed(/** @scrutinizer ignore-unused */ Events\JobProcessed $event)
    {
        if ($this->response === null) {
            $this->status = self::STATUS_PROCESSED;
            $this->response = new Response('Task completed successfully');
        }
    }

    public function getResult(): ?string
    {
        return $this->status;
    }
}
