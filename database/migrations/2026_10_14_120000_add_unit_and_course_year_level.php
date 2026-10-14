<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data the office's official report formats print and nothing on the app
 * screens could supply until now.
 *
 * `equipment.unit` is what one of `quantity` is counted in ("pc", "set",
 * "box"), printed beside every quantity on paper. Defaults to "pc" so every
 * existing row has a sensible unit without a backfill.
 *
 * `users.course` and `users.year_level` fill the "Course & Year" column.
 * Nullable: no account that exists today has them, staff never will, and
 * only Student accounts are asked for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('unit', 20)->default('pc')->after('quantity');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('course', 50)->nullable()->after('contact_number');
            $table->string('year_level', 20)->nullable()->after('course');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['course', 'year_level']);
        });
    }
};
