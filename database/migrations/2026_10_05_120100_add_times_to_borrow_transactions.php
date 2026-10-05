<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A time-limited loan is due at a time of day ("back by 3:30 PM"), so the two
 * loan dates become datetimes. A date-only loan keeps its date at 00:00:00 and
 * `timed` stays false, which tells BorrowTransaction::dueAt() to read the due
 * date as the end of that day, exactly as it did before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_transactions', function (Blueprint $table) {
            $table->dateTime('borrow_date')->change();
            $table->dateTime('return_date')->nullable()->change();
            $table->boolean('timed')->default(false)->after('return_date');
        });
    }

    /**
     * Going back to `date` would silently drop the time of every timed loan,
     * and with it the only record of when each one was due. Refuse instead;
     * the timed loans have to be dealt with by hand first.
     */
    public function down(): void
    {
        $timed = DB::table('borrow_transactions')->where('timed', true)->count();

        if ($timed > 0) {
            throw new RuntimeException(
                "Cannot roll back: {$timed} borrow transaction(s) are timed loans, and converting "
                .'borrow_date/return_date back to DATE would discard their due times.'
            );
        }

        Schema::table('borrow_transactions', function (Blueprint $table) {
            $table->dropColumn('timed');
        });

        Schema::table('borrow_transactions', function (Blueprint $table) {
            $table->date('borrow_date')->change();
            $table->date('return_date')->nullable()->change();
        });
    }
};
