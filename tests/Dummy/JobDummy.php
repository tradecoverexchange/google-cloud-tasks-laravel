<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests\Dummy;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobDummy implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $fireException = false;
    public bool $fail = false;
    public bool $release = false;
    public array|null $configuredBackoff = null;
    public int $tries = 3;

    public static function make(): self
    {
        return new self();
    }

    public function handle(): void
    {
        if ($this->fireException) {
            throw new \RuntimeException('Error happened');
        }
        if ($this->fail) {
            $this->fail(new \RuntimeException('Marked as failure'));
        }
        if ($this->release) {
            $this->release(60);
        }
    }

    public function mockExceptionFiring(): self
    {
        $this->fireException = true;

        return $this;
    }

    public function mockFailing(): self
    {
        $this->fail = true;

        return $this;
    }

    public function withBackoff(array|null $backoff = [10]): self
    {
        $this->configuredBackoff = $backoff;

        return $this;
    }

    public function withTries(int $tries = 5): self
    {
        $this->tries = $tries;

        return $this;
    }

    public function backoff(): array|null
    {
        return $this->configuredBackoff;
    }

    public function mockRelease(): self
    {
        $this->release = true;

        return $this;
    }
}
