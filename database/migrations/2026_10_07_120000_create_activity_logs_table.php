<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The activity log: an append-only audit trail of what happened, who did it
 * and to what, for the reports screen.
 *
 * Every person and item is stored twice: as a foreign key, which goes null when
 * the row is deleted, and as a name snapshot taken when the event happened,
 * which does not. Deleting an account or an item must not empty the history
 * it is part of, so none of the keys cascade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // When it happened, in the app timezone. Not created_at: a
            // backfilled row is written today about something from last month.
            $table->dateTime('occurred_at')->index();
            // A stable key from ActivityLog::TYPES, never a display label.
            $table->string('type', 40)->index();

            // Who did it. A null actor_id with a null actor_name is the system
            // (the scheduler); a null actor_id with a name is a deleted account,
            // or "Not recorded" where the old schema never stored who.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 120)->nullable();
            $table->string('actor_role', 20)->nullable();

            // The person it happened to, e.g. the borrower.
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_name')->nullable();

            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->string('equipment_name')->nullable();

            $table->foreignId('borrow_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->nullable();

            // Status labels before and after the event (Borrowed, Returned,
            // Void, Approved, Retired, Suspended…).
            $table->string('status_from', 30)->nullable();
            $table->string('status_to', 30)->nullable();

            $table->text('details')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();

            // 'live' when written as the change happened, 'backfill' when
            // rebuilt from older tables by `php artisan activity:backfill`.
            $table->string('source', 12)->default('live');
            // Deterministic per source row, so the backfill can run again
            // without adding anything twice.
            $table->string('backfill_key', 80)->nullable()->unique();

            $table->timestamps();

            $table->index(['equipment_id', 'occurred_at']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index(['subject_user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
