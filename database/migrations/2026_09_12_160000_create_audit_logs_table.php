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
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event')->index();
                $table->string('level', 20)->default('info')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_email')->nullable()->index();
                $table->string('ip', 45)->nullable()->index();
                $table->string('method', 10)->nullable();
                $table->string('path')->nullable();
                $table->json('payload')->nullable();
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
        Schema::dropIfExists('audit_logs');
    }
};
