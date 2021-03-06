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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public static function make()
    {
        return new self();
    }

    public function handle()
    {
        Cache::put('test-url', app()->make('url')->to('test'));
    }
}
