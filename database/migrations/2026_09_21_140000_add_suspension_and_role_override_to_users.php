<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
