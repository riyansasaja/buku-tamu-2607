<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('whatsapp')->nullable()->after('email');
            $table->string('whatsapp_hash', 64)->nullable()->unique()->after('whatsapp');
            $table->timestamp('activated_at')->nullable()->after('is_active');
        });
        DB::table('users')->update(['activated_at' => now()]);

        Schema::create('user_activation_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'used_at', 'revoked_at']);
        });

        Schema::create('user_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_activation_token_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('type', 50)->default('admin_activation');
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('provider_message_id')->nullable();
            $table->string('provider_request_id')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_deliveries');
        Schema::dropIfExists('user_activation_tokens');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['whatsapp_hash']);
            $table->dropColumn(['whatsapp', 'whatsapp_hash', 'activated_at']);
        });
    }
};
