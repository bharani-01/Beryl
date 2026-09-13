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
        if (! Schema::hasTable('ip_locations')) {
            Schema::create('ip_locations', function (Blueprint $table) {
                $table->string('ip', 45)->primary();
                $table->string('country', 100)->nullable();
                $table->string('country_code', 10)->nullable()->index();
                $table->string('flag', 10)->nullable();
                $table->string('city', 100)->nullable()->index();
                $table->string('region', 100)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('isp', 150)->nullable();
                $table->string('org', 150)->nullable();
                $table->string('provider_used', 50)->nullable();
                $table->boolean('is_private')->default(false);
                $table->timestamps();

                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_locations');
    }
};
