<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

class PublishPostCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish posts that are scheduled for publishing at the current time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(BlogPost::where('created_at', '<=', now())->where('status', 3)->exists()) {
            BlogPost::where('created_at', '<=', now())->where('status', 3)->update(['status' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $this->info('Posts published successfully.');
        }
    }
}
