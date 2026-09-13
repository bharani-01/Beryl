<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instance_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('instance_settings', 'bypass_email_verification')) {
                $table->boolean('bypass_email_verification')->default(false)->after('is_registration_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('instance_settings', function (Blueprint $table) {
            if (Schema::hasColumn('instance_settings', 'bypass_email_verification')) {
                $table->dropColumn('bypass_email_verification');
            }
        });
    }
};
