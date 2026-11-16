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
            $table->enum('status', ['Borrowed', 'Returned', 'Overdue', 'Issued'])->default('Borrowed')->change();
        });
    }

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
