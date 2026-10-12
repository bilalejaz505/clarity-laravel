<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_secrets', function (Blueprint $table): void {
            $table->id();
            $table->string('provider')->unique();
            $table->text('ciphertext');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_secrets');
    }
};
