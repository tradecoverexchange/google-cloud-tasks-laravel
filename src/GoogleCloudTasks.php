<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Response;
use Illuminate\Queue\Events;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class GoogleCloudTasks
{
    protected $response = null;
    /**
     * @var FailedJobProviderInterface
     */
    private $failedJobProvider;

    public function __construct(FailedJobProviderInterface $failedJobProvider)
    {
        $this->failedJobProvider = $failedJobProvider;
    }

    /**
     * @return null|Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function subscribe(Dispatcher $dispatcher)
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
            $this->response = new Response($event->exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    protected function jobProcessed(Events\JobProcessed $event)
    {
        if ($this->response === null) {
            $this->response = new Response('Task completed successfully');
        }
    }
}
