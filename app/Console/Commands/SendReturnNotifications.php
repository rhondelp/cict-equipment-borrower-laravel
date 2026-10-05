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
    protected $description = 'Mark overdue loans and email reminders for loans due back today';

    /**
     * Execute the console command.
     *
     * Scheduled once a day (bootstrap/app.php). The sweep marks a loan Overdue
     * by BorrowTransaction::overdue() — timed loans by their time, others by
     * their day, Issued never — so a timed loan that falls due after this runs
     * is marked on the next run. No screen waits for that: derivedStatus()
     * reads overdue off dueAt() on every render.
     */
    public function handle()
    {
        $controller = new BorrowTransactionController;
        $summary = $controller->sendReturnAlertNotification();

        $this->info($summary);
    }
}
