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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); /* Name identifier for the QR code */
            $table->enum('type', ['post', 'exhibition'])->default('post'); /* Type of the QR code */
            $table->foreignId('museum_id')->nullable()->constrained()->nullOnDelete(); /* Museum association */
            $table->foreignId('qr_image_id')->nullable()->constrained('media')->nullOnDelete(); /* QR code image association */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
