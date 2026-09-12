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
        if (Schema::hasTable('instance_settings') && ! Schema::hasColumn('instance_settings', 'razorpay_key_id')) {
            Schema::table('instance_settings', function (Blueprint $table) {
                $table->string('razorpay_key_id')->nullable();
                $table->text('razorpay_key_secret')->nullable();
                $table->string('razorpay_webhook_secret')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('instance_settings') && Schema::hasColumn('instance_settings', 'razorpay_key_id')) {
            Schema::table('instance_settings', function (Blueprint $table) {
                $table->dropColumn(['razorpay_key_id', 'razorpay_key_secret', 'razorpay_webhook_secret']);
            });
        }
    }
};
