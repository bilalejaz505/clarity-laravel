<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('paypal_id', 100)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider_custom_id')->nullable();
            $table->string('plan_key');
            $table->string('status')->index();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('paid_until')->nullable();
            $table->timestamp('provider_updated_at')->nullable();
            $table->timestamp('refund_cutoff')->nullable();
            $table->longText('plan_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
