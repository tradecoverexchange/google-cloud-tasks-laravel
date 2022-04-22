<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Tests\Dummy;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class JobUrlGeneration implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public static function make(): self
    {
        return new self();
    }

    public function handle(): void
    {
        Cache::put('test-url', app()->make('url')->to('test'));
    }
}
