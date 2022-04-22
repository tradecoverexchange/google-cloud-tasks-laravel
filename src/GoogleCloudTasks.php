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

    protected Response|null $response = null;
    protected string|null $status = null;

    public function __construct(protected FailedJobProviderInterface $failedJobProvider)
    {
    }

    public function getResponse(): Response|null
    {
        return $this->response;
    }

    public function subscribe(EventBus $dispatcher): void
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

    protected function jobFailed(Events\JobFailed $event): void
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

    protected function jobExceptionOccurred(Events\JobExceptionOccurred $event): void
    {
        if ($this->response === null) {
            $this->status = self::STATUS_EXCEPTION_THROWN;
            $this->response = new Response($event->exception->getMessage(), Response::HTTP_RESET_CONTENT);
        }
    }

    protected function jobProcessed(Events\JobProcessed $event): void
    {
        if ($this->response === null) {
            $this->status = self::STATUS_PROCESSED;
            if ($event->job->isReleased()) {
                $this->response = new Response('Job released', Response::HTTP_PARTIAL_CONTENT);

                return;
            }
            $this->response = new Response('', Response::HTTP_NO_CONTENT);
        }
    }

    public function getResult(): string|null
    {
        return $this->status;
    }
}
