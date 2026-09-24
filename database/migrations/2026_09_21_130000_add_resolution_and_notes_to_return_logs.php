<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Return logs are an audit record: they are read when something is damaged,
 * missing or disputed. Two things that reading needs were missing.
 *
 * 1. Whether a damaged or lost item was ever *dealt with*. Without it the
 *    screen cannot lead with what still needs follow-up, and a damaged return
 *    from March looks exactly like one from this morning.
 *
 * 2. Somewhere to put a correction. The record itself stays immutable — there
 *    is no edit and no delete — so a correction is appended as a new note
 *    carrying its own author and timestamp, and the original survives.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_logs', function (Blueprint $table) {
            $table->text('resolution')->nullable()->after('remarks');
            $table->timestamp('resolved_at')->nullable()->after('resolution');
            $table->foreignId('resolved_by')->nullable()->after('resolved_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('return_log_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_log_id')->constrained()->cascadeOnDelete();
            // Nullable so removing a staff account does not erase the note they
            // wrote — the audit trail outlives the employment.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_log_notes');

        Schema::table('return_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn(['resolution', 'resolved_at']);
        });
    }
};
