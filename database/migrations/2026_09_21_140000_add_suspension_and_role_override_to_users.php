<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two states the users screen needed and the schema could not express.
 *
 * **Suspension** is not deactivation. A deactivated account cannot sign in at
 * all; a suspended one works normally but cannot borrow — it is the sanction
 * the terms of service already describe ("the department may decline further
 * requests from an account with items still overdue") and which until now had
 * to be applied by an admin remembering to decline each request by hand.
 *
 * **A role override** is a privilege decision. Role is derived from the school
 * email domain; where an admin overrides that derivation, the who, the when and
 * the why are recorded, because an unexplained role change is the one edit on
 * this screen nobody can reconstruct afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('deactivated_at');
            $table->string('suspension_reason', 500)->nullable()->after('suspended_at');
            $table->foreignId('suspended_by')->nullable()->after('suspension_reason')
                ->constrained('users')->nullOnDelete();

            $table->timestamp('role_overridden_at')->nullable()->after('suspended_by');
            $table->string('role_override_reason', 500)->nullable()->after('role_overridden_at');
            $table->foreignId('role_overridden_by')->nullable()->after('role_override_reason')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropConstrainedForeignId('role_overridden_by');
            $table->dropColumn([
                'suspended_at', 'suspension_reason',
                'role_overridden_at', 'role_override_reason',
            ]);
        });
    }
};
