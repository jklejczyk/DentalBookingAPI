<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_type_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('dentist_id');
            $table->string('status')->default(\App\Http\Enums\AppointmentStatusEnum::BOOKED);
            $table->dateTime('start');
            $table->dateTime('end');
            $table->timestamps();

            $table->foreign('appointment_type_id')->references('id')->on('appointment_types');
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('dentist_id')->references('id')->on('dentists');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
