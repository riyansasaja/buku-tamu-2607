<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table): void {
            $table->id();
            $table->string('visit_number', 32)->unique();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('guest_name', 150);
            $table->string('address', 500);
            $table->text('visit_purpose');
            $table->string('photo_path');
            $table->string('photo_mime_type', 32);
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('arrived_at')->index();
            $table->char('idempotency_key_hash', 64)->unique();
            $table->char('request_fingerprint', 64);
            $table->timestamps();

            $table->index(['employee_id', 'arrived_at']);
            $table->index(['status', 'arrived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
