<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->boolean('require_otp')->default(true)->after('share_mode');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->boolean('allow_invitation_without_otp')->default(false)->after('allow_static_links');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('allow_invitation_without_otp');
        });

        Schema::table('bundles', function (Blueprint $table) {
            $table->dropColumn('require_otp');
        });
    }
};
