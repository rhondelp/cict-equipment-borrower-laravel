<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->string('year_level');
            $table->string('block_name');
            $table->string('subject_code');
            $table->string('subject_name');
            $table->string('schedule_time');
            $table->string('room');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
