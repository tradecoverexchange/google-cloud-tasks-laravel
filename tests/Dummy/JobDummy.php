<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests\Dummy;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobDummy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var bool
     */
    private $fireException = false;
    /**
     * @var bool
     */
    private $fail = false;

    public static function make()
    {
        return new self();
    }

    public function handle()
    {
        if ($this->fireException) {
            throw new \RuntimeException('Error happened');
        }
        if ($this->fail) {
            $this->fail(new \RuntimeException('Marked as failure'));
        }
    }

    public function mockExceptionFiring()
    {
        $this->fireException = true;

        return $this;
    }

    public function mockFailing()
    {
        $this->fail = true;

        return $this;
    }
}
