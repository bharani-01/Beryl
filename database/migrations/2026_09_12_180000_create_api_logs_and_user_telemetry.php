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
        // 1. Add user activity and telemetry tracking columns
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'last_active_at')) {
                    $table->timestamp('last_active_at')->nullable()->index()->after('email');
                }
                if (! Schema::hasColumn('users', 'total_api_calls')) {
                    $table->unsignedBigInteger('total_api_calls')->default(0)->after('last_active_at');
                }
                if (! Schema::hasColumn('users', 'last_api_call_at')) {
                    $table->timestamp('last_api_call_at')->nullable()->after('total_api_calls');
                }
                if (! Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('last_api_call_at');
                }
                if (! Schema::hasColumn('users', 'last_login_ip')) {
                    $table->string('last_login_ip')->nullable()->after('last_login_at');
                }
            });
        }

        // 2. Create dedicated api_logs table for end-to-end API auditability
        if (! Schema::hasTable('api_logs')) {
            Schema::create('api_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('team_id')->nullable()->index();
                $table->unsignedBigInteger('token_id')->nullable();
                $table->string('token_name')->nullable();
                $table->string('method', 10);
                $table->string('path');
                $table->integer('status_code')->default(200);
                $table->decimal('duration_ms', 8, 2)->default(0.00);
                $table->string('ip_address', 45);
                $table->string('user_agent')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('api_logs')) {
            Schema::dropIfExists('api_logs');
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $cols = [];
                foreach (['last_active_at', 'total_api_calls', 'last_api_call_at', 'last_login_at', 'last_login_ip'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $cols[] = $col;
                    }
                }
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
