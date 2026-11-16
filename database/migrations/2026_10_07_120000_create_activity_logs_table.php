<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('occurred_at')->index();
            $table->string('type', 40)->index();

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 120)->nullable();
            $table->string('actor_role', 20)->nullable();

            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_name')->nullable();

            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->string('equipment_name')->nullable();

            $table->foreignId('borrow_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->nullable();

            $table->string('status_from', 30)->nullable();
            $table->string('status_to', 30)->nullable();

            $table->text('details')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->string('source', 12)->default('live');
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
