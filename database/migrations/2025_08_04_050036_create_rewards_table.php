<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('challenge_type');
            $table->longText('description')->nullable();
            $table->date('expiration_date');
            $table->unsignedBigInteger('purchase_point')->default(0);
            $table->enum('status', ['Enable', 'Disable'])->default('Enable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
