<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A reminder that is sent and not recorded is a reminder nobody can prove was
 * sent — so the loans screen could never answer "have we chased this yet?",
 * and an admin with an overdue item in front of them had no way to tell a
 * first nudge from a fourth.
 *
 * Notifications already existed and the nightly sweep already wrote them; what
 * was missing was which loan a row belongs to. Nullable, because every row
 * written before this has no answer, and the nightly sweep's rows are about a
 * person's whole position rather than one transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('borrow_transaction_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('borrow_transaction_id');
        });
    }
};
