<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How an item leaves the room: lent and brought back (returnable), lent for a
 * set number of hours (time_limited), or handed over for good
 * (non_returnable). The values and their labels live on Equipment as
 * constants; this column only stores the key.
 *
 * Separate from `category`, which is free-text grouping for the borrower's
 * list and says nothing about whether an item comes back. Every existing row
 * is a lend-and-return item, hence the default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('loan_type', 20)->default('returnable')->after('category')->index();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['loan_type']);
            $table->dropColumn('loan_type');
        });
    }
};
