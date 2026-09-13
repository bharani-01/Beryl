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
        if (Schema::hasTable('forensic_audit_logs')) {
            Schema::table('forensic_audit_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('forensic_audit_logs', 'device_type')) {
                    $table->string('device_type', 30)->nullable()->index()->after('user_agent');
                }
                if (! Schema::hasColumn('forensic_audit_logs', 'device_summary')) {
                    $table->string('device_summary', 150)->nullable()->after('device_type');
                }
                if (! Schema::hasColumn('forensic_audit_logs', 'country')) {
                    $table->string('country', 100)->nullable()->after('device_summary');
                }
                if (! Schema::hasColumn('forensic_audit_logs', 'country_code')) {
                    $table->string('country_code', 10)->nullable()->index()->after('country');
                }
                if (! Schema::hasColumn('forensic_audit_logs', 'city')) {
                    $table->string('city', 100)->nullable()->index()->after('country_code');
                }
                if (! Schema::hasColumn('forensic_audit_logs', 'region')) {
                    $table->string('region', 100)->nullable()->after('city');
                }
                if (! Schema::hasColumn('forensic_audit_logs', 'isp')) {
                    $table->string('isp', 150)->nullable()->after('region');
                }
                if (! Schema::hasColumn('forensic_audit_logs', 'location_summary')) {
                    $table->string('location_summary', 200)->nullable()->after('isp');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('forensic_audit_logs')) {
            Schema::table('forensic_audit_logs', function (Blueprint $table) {
                $columns = ['device_type', 'device_summary', 'country', 'country_code', 'city', 'region', 'isp', 'location_summary'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('forensic_audit_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
