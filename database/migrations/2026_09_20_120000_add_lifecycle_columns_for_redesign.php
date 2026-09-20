<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns the redesigned screens need in order to stop lying.
 *
 * Every destructive action in the admin UI now leads with a non-destructive
 * default, and a hard delete is refused outright while history still points at
 * the row. That needs somewhere to record "no longer in use but still real":
 * equipment.retired_at, users.deactivated_at, borrow_transactions.voided_at.
 *
 * item_requests gains the decision trail. A decline without a reason is no
 * longer accepted, so the reason needs a column rather than being smuggled
 * into `remarks`, which belongs to the borrower.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->timestamp('retired_at')->nullable()->after('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable()->after('contact_number');
        });

        Schema::table('borrow_transactions', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('remarks');
            $table->string('void_reason', 500)->nullable()->after('voided_at');
        });

        Schema::table('item_requests', function (Blueprint $table) {
            $table->string('decision_reason', 500)->nullable()->after('remarks');
            $table->timestamp('decided_at')->nullable()->after('decision_reason');
            $table->foreignIdFor(User::class, 'decided_by')->nullable()->after('decided_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('retired_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('deactivated_at');
        });

        Schema::table('borrow_transactions', function (Blueprint $table) {
            $table->dropColumn(['voided_at', 'void_reason']);
        });

        Schema::table('item_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['decision_reason', 'decided_at']);
        });
    }
};
