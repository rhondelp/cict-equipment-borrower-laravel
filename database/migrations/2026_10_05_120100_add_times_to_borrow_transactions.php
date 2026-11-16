<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
