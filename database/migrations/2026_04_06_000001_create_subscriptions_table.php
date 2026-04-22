<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscription architecture for Phase 5.
 *
 * Strategy: use RevenueCat as the single source of truth for both
 * Google Play and App Store subscriptions. This table stores a local
 * mirror of the subscription status so the backend can gate premium
 * features without calling RevenueCat on every request.
 *
 * RevenueCat webhooks will update this table whenever a subscription
 * event occurs (new purchase, renewal, cancellation, expiration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // RevenueCat identifiers
            $table->string('rc_customer_id')->nullable()->index();

            // Store identifiers
            $table->string('store')->comment('app_store | play_store');
            $table->string('product_id');                   // e.g. "community_dhikr_monthly"
            $table->string('store_transaction_id')->nullable()->index();

            // Status tracking
            $table->string('status')->default('active')
                  ->comment('active | expired | cancelled | grace_period');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->boolean('auto_renew')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Add a quick flag on users for fast premium checks
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_premium')->default(false)->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_premium');
        });
    }
};
