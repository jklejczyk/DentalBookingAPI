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
        Schema::create('dentist_blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dentist_id');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('dentist_id')->references('id')->on('dentists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dentist_blocked_slots');
    }
};
