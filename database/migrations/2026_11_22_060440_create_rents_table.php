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
        Schema::create('rents', function (Blueprint $table) {
            $table->id();
 
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('garage_id', 4);
            $table->foreign('garage_id')->references('id')->on('garages');
 
            $table->string('rentType');
            $table->string('rentStatus');
            $table->integer('dayAmount')->nullable();
            $table->date('startDate');
            $table->date('endDate');
            $table->string('paymentProof');
            $table->integer('totalCost');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rents');
    }
};
