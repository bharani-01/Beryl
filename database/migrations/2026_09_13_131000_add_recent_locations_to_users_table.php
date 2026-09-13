<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'recent_locations')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('recent_locations')->nullable()->after('last_login_ip');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'recent_locations')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('recent_locations');
            });
        }
    }
};
