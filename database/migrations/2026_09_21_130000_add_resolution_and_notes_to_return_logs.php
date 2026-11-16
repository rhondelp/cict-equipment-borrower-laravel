<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
