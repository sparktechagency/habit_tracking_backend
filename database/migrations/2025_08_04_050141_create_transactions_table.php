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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('payment_intent_id');
            $table->string('card_number')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('plan_name');
            $table->timestamp('date')->nullable();
            $table->timestamp('renewal')->nullable();
            $table->decimal('amount',10,2);
            $table->string('status')->default('Completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
