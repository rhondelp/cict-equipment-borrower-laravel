<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every account — student and instructor alike — is on @nmsc.edu.ph, so the
 * address can no longer say which role someone holds.
 *
 * The sign-up form now asks, but asking is a request, not a grant: a new
 * account is always created as a Student, and choosing Instructor stamps this
 * column. The account works as a Student straight away; an admin confirms or
 * declines the instructor request on the users screen, and either decision
 * clears it. Confirmation is recorded through the existing role_overridden_*
 * columns, so the row says who switched it on and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('instructor_requested_at')->nullable()->after('role_overridden_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('instructor_requested_at');
        });
    }
};
