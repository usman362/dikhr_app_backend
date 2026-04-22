<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a `dhikr` column to campaigns so each campaign can declare which
     * dhikr it is for (e.g. "SubhanAllah", "Astaghfirullah"). The mobile
     * counter screen locks the selector to this dhikr when the user enters
     * the counter from a campaign.
     *
     * Nullable for backward compatibility — existing campaigns stay unlocked.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('dhikr', 50)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('dhikr');
        });
    }
};
