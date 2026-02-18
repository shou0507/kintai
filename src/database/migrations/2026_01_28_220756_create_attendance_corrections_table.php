<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();

            $table->date('date');

            $table->string('clock_in', 5)->nullable();
            $table->string('clock_out', 5)->nullable();

            $table->string('break1_in', 5)->nullable();
            $table->string('break1_out', 5)->nullable();
            $table->string('break2_in', 5)->nullable();
            $table->string('break2_out', 5)->nullable();

            $table->text('note')->nullable();

            $table->string('status', 20)->default('pending');

            $table->index(['attendance_id', 'status']);
            $table->index(['user_id', 'status']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_corrections');
    }
}
