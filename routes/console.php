<?php

use App\Ai\AiConfig;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:resume', function () {
    AiConfig::resume();
    $this->info('AI features resumed.');
})->purpose('Resume AI features after topping up provider credit');
