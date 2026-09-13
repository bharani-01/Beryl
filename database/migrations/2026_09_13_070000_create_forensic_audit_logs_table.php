<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('forensic_audit_logs')) {
            Schema::create('forensic_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('event_id')->unique();
                $table->unsignedBigInteger('sequence_number')->unique();
                $table->string('operation_id', 100)->nullable()->index();
                $table->uuid('parent_event_id')->nullable()->index();
                $table->uuid('root_event_id')->nullable()->index();

                $table->string('event_type', 100)->index();
                $table->string('event_category', 50)->index();
                $table->smallInteger('event_version')->default(1);
                $table->string('severity', 20)->default('INFORMATIONAL')->index();

                $table->string('action_operation', 30)->index();
                $table->string('action_result', 20)->default('SUCCESS')->index();
                $table->text('action_reason')->nullable();
                $table->string('ticket_id', 100)->nullable();
                $table->string('change_request_id', 100)->nullable();
                $table->string('approval_id', 100)->nullable();

                // Actor & Source Taxonomy
                $table->string('actor_type', 30)->default('HUMAN')->index();
                $table->string('actor_id', 100)->nullable()->index();
                $table->string('actor_email', 255)->nullable()->index();
                $table->string('actor_role', 50)->nullable();
                $table->string('source_type', 30)->default('DASHBOARD')->index();

                // Tenant & Context Isolation
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->unsignedBigInteger('environment_id')->nullable()->index();
                $table->string('environment_name', 50)->nullable();

                // Target Resource
                $table->string('target_type', 100)->nullable()->index();
                $table->string('target_id', 255)->nullable()->index();
                $table->string('target_name', 255)->nullable();

                // Deployment Provenance
                $table->json('deployment_provenance')->nullable();

                // Network & Request Origin
                $table->string('ip_address', 45)->nullable()->index();
                $table->string('user_agent', 255)->nullable();
                $table->string('route', 255)->nullable();
                $table->string('http_method', 10)->nullable();
                $table->integer('status_code')->nullable();
                $table->string('correlation_id', 100)->nullable();
                $table->string('request_id', 100)->nullable();
                $table->string('session_id', 100)->nullable();

                // 3-Tier Clock Timestamps
                $table->timestampTz('event_time')->useCurrent()->index();
                $table->timestampTz('received_at')->useCurrent();
                $table->timestampTz('persisted_at')->useCurrent();

                // Canonical Structured Payloads
                $table->json('actor');
                $table->json('target');
                $table->json('action');
                $table->json('request');
                $table->json('changes')->nullable();
                $table->json('authentication');
                $table->json('source');
                $table->json('security');

                // Cryptographic Hash Chaining
                $table->string('previous_event_hash', 64);
                $table->string('event_hash', 64)->index();

                $table->timestamps();

                // Composite indexes for rapid forensic investigation
                $table->index(['organization_id', 'event_time'], 'idx_fal_org_time');
                $table->index(['event_type', 'event_time'], 'idx_fal_type_time');
                $table->index(['event_category', 'severity', 'event_time'], 'idx_fal_cat_sev');
                $table->index(['actor_id', 'event_time'], 'idx_fal_actor_time');
                $table->index(['target_type', 'target_id'], 'idx_fal_target');
            });

            // Enforce explicit database-level immutability
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::unprepared("
                    CREATE OR REPLACE FUNCTION protect_forensic_audit_logs()
                    RETURNS TRIGGER AS \$\$
                    BEGIN
                        RAISE EXCEPTION 'SECURITY VIOLATION: forensic_audit_logs is append-only. Modification (UPDATE) and deletion (DELETE) are strictly prohibited.';
                    END;
                    \$\$ LANGUAGE plpgsql;

                    DROP TRIGGER IF EXISTS trg_forensic_audit_logs_protect ON forensic_audit_logs;
                    CREATE TRIGGER trg_forensic_audit_logs_protect
                    BEFORE UPDATE OR DELETE ON forensic_audit_logs
                    FOR EACH ROW EXECUTE FUNCTION protect_forensic_audit_logs();
                ");
            } elseif ($driver === 'sqlite') {
                DB::unprepared("
                    CREATE TRIGGER IF NOT EXISTS trg_fal_no_update BEFORE UPDATE ON forensic_audit_logs
                    BEGIN
                        SELECT RAISE(FAIL, 'SECURITY VIOLATION: forensic_audit_logs is append-only. Modification is prohibited.');
                    END;

                    CREATE TRIGGER IF NOT EXISTS trg_fal_no_delete BEFORE DELETE ON forensic_audit_logs
                    BEGIN
                        SELECT RAISE(FAIL, 'SECURITY VIOLATION: forensic_audit_logs is append-only. Deletion is prohibited.');
                    END;
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_forensic_audit_logs_protect ON forensic_audit_logs;');
            DB::unprepared('DROP FUNCTION IF EXISTS protect_forensic_audit_logs();');
        } elseif ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_fal_no_update;');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_fal_no_delete;');
        }

        Schema::dropIfExists('forensic_audit_logs');
    }
};
