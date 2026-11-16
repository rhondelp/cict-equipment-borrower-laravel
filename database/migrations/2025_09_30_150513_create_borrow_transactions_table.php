<?php
use App\Models\User;
use App\Models\Equipment;
use App\Models\ClassSchedule;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Equipment::class)->constrained()->onDelete('cascade');
            $table->date('borrow_date');
            $table->date('return_date')->nullable();
            $table->integer('quantity');
            $table->string('purpose');
            $table->enum('status', ['Borrowed', 'Returned', 'Overdue'])->default('Borrowed');
            $table->text('remarks')->nullable();
            $table->foreignIdFor(ClassSchedule::class)->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_transactions');
    }
};
