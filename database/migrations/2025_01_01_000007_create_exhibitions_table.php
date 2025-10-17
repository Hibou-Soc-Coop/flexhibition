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
        Schema::create('exhibitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('museum_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained()->nullOnDelete();
            $table->json('name'); // Campo traducibile
            $table->json('description')->nullable(); // Campo traducibile
            $table->json('credits')->nullable(); // Campo traducibile
            $table->foreignId('audio_id')->nullable()->constrained('media')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibitions');
    }
};
