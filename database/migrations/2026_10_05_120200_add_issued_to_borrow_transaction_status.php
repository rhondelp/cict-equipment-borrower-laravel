<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `Issued` is the status of a non-returnable hand-over: the units left the
 * room for good. It is not "out" — nothing is expected back, so it is never
 * overdue — and Equipment::unitsIssued() counts it separately from
 * Equipment::unitsOut().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_transactions', function (Blueprint $table) {
            $table->enum('status', ['Borrowed', 'Returned', 'Overdue', 'Issued'])->default('Borrowed')->change();
        });
    }

    /**
     * Shrinking the enum with Issued rows present would make MySQL reject the
     * ALTER, or in non-strict mode blank the status of every issued row. There
     * is no honest status to map them to, so refuse.
     */
    public function down(): void
    {
        $issued = DB::table('borrow_transactions')->where('status', 'Issued')->count();

        if ($issued > 0) {
            throw new RuntimeException(
                "Cannot roll back: {$issued} borrow transaction(s) have status 'Issued', "
                .'which the previous enum cannot hold.'
            );
        }

        Schema::table('borrow_transactions', function (Blueprint $table) {
            $table->enum('status', ['Borrowed', 'Returned', 'Overdue'])->default('Borrowed')->change();
        });
    }
};
