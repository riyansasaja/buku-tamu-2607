<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('channel', 20)->default('whatsapp');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('provider_message_id', 100)->nullable();
            $table->string('provider_request_id', 100)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['visit_id', 'type']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
