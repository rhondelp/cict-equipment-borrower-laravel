<?php

namespace App\Console\Commands;

use App\Http\Controllers\BorrowTransactionController;
use Illuminate\Console\Command;

class SendReturnNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Run with: php artisan notifications:return
     */
    protected $signature = 'notifications:return';

    /**
     * The console command description.
     */
    protected $description = 'Send return reminder notifications to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Call the controller method
        $controller = new BorrowTransactionController;
        $controller->sendReturnAlertNotification();

        $this->info('Return notifications sent successfully.');
    }
}
