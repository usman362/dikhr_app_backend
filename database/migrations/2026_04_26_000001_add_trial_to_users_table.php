<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add 7-day free trial system to users table.
 *
 * Subscription-only model (client decision):
 *   - Every user gets a 7-day free trial on registration
 *   - After trial → must subscribe via RevenueCat to keep using the app
 *   - No free tier — app fully locks without active access
 *
 * Backfill: any existing users without trial dates get a fresh 7-day
 * trial starting now, so the launch doesn't lock them out instantly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trial_started_at')->nullable()->after('is_premium');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_started_at');
        });

        // Backfill: existing users get a fresh 7-day trial from now.
        // (No grandfathering — client confirmed: uniform rules for all users.)
        DB::table('users')
            ->whereNull('trial_started_at')
            ->update([
                'trial_started_at' => now(),
                'trial_ends_at'    => now()->addDays(7),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['trial_started_at', 'trial_ends_at']);
        });
    }
};
