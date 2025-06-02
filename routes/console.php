<?php
// routes/console.php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('post:publish', function () {
    $this->call('post:publish');
})->purpose('Publish posts that are scheduled for publishing at the current time');

Schedule::command('post:publish')
    ->everyMinute();

Artisan::command('trash:empty', function () {
    $this->call('trash:empty');
})->purpose('Empty All Trash of Blog Posts and Categories older than 30 days');

Schedule::command('trash:empty')
    ->daily();

Artisan::command('log:clear', function () {
    $this->call('log:clear');
})->purpose('Clear the application log files');

Schedule::command('log:clear')
    ->weekly();
