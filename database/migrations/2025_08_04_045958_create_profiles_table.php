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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedInteger('level')->default(0);
            $table->unsignedInteger('total_habits')->default(0);
            $table->unsignedInteger('longest_streaking')->default(0);
            $table->unsignedInteger('completed_challenges')->default(0);
            $table->unsignedInteger('say_no')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
