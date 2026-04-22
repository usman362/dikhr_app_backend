<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('invite_code', 8)->nullable()->unique()->after('description');
        });

        // Backfill existing groups with invite codes.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        \DB::table('groups')->whereNull('invite_code')->orderBy('id')->each(function ($group) use ($alphabet) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            \DB::table('groups')->where('id', $group->id)->update(['invite_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('invite_code');
        });
    }
};
