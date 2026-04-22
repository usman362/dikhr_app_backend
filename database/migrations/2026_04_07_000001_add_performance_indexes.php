<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for frequently queried columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Campaign status filtering + ordering
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index(['group_id', 'status']);
        });

        // Contribution aggregation queries
        Schema::table('dhikr_contributions', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
            $table->index('created_at'); // For daily/weekly aggregates
        });

        // User premium checks
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['group_id', 'status']);
        });

        Schema::table('dhikr_contributions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin']);
        });
    }
};
