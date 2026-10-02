<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A free-text category per item ("Projectors", "Cables"), used to group the
 * borrower's request list and to label the shelf panel.
 *
 * Deliberately a column, not a categories table: the admin picks one already
 * in use or types a new one in the equipment form, and
 * Equipment::canonicalCategory() folds "cables " onto an existing "Cables" so
 * the list does not split on case or stray spaces. Nullable, because every
 * item that exists today has none and still has to render.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('category', 60)->nullable()->after('description')->index();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
