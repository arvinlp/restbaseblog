<?php

namespace App\Console\Commands;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Console\Command;

class EmptyTrashCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trash:empty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Empty All Trash of Blog Posts and Categories older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Emptying trash...');
        if(BlogPost::onlyTrashed()->where('deleted_at', '<=', now()->subDays(30))->forceDelete()) {
            $this->info('Blog Post Trash emptied successfully.');
        } else {
            $this->error('No items to empty from the Blog Post trash.');
        }
        if(BlogCategory::onlyTrashed()->where('deleted_at', '<=', now()->subDays(30))->forceDelete()) {
            $this->info('Blog Category Trash emptied successfully.');
        } else {
            $this->error('No items to empty from the Blog Category trash.');
        }
        // Logic to empty the trash
        $this->info('All Trash emptied successfully.');
    }
}
