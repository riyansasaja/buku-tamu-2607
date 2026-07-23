<?php

use App\Support\WhatsAppNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->text('guest_whatsapp')->nullable()->after('address');
        });

        Schema::create('visit_decision_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        DB::table('employees')->select(['id', 'notification_contact'])->orderBy('id')->each(function (object $employee): void {
            try {
                $plain = is_string($employee->notification_contact) ? Crypt::decryptString($employee->notification_contact) : null;
            } catch (Throwable) {
                $plain = null;
            }

            $normalized = WhatsAppNumber::normalize($plain);
            DB::table('employees')->where('id', $employee->id)->update([
                'notification_contact' => $normalized === null ? null : Crypt::encryptString($normalized),
                'is_active' => $normalized !== null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_decision_tokens');
        Schema::table('visits', function (Blueprint $table): void {
            $table->dropColumn('guest_whatsapp');
        });
    }
};
