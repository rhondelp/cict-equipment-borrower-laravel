<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
