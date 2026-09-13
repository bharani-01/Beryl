<?php

namespace App\Observers;

use App\Services\Audit\AuditRedactor;
use App\Services\Audit\ForensicAuditService;
use Illuminate\Database\Eloquent\Model;

class ControlPlaneModelObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->recordModelAudit($model, 'CREATE', 'SUCCESS');
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $dirty = $model->getDirty();
        if (empty($dirty)) {
            return;
        }

        $before = [];
        $after = [];
        $changedFields = [];

        foreach ($dirty as $key => $newValue) {
            // Ignore low-value volatile timestamps unless significant
            if (in_array($key, ['updated_at', 'last_online_at'], true)) {
                continue;
            }

            $originalValue = $model->getOriginal($key);

            if (AuditRedactor::isSensitiveKey($key)) {
                $before[$key] = '[REDACTED:HMAC-'.substr(AuditRedactor::fingerprint((string) $originalValue), 0, 16).']';
                $after[$key] = '[REDACTED:HMAC-'.substr(AuditRedactor::fingerprint((string) $newValue), 0, 16).']';
            } else {
                $before[$key] = $originalValue;
                $after[$key] = $newValue;
            }

            $changedFields[] = $key;
        }

        if (empty($changedFields)) {
            return;
        }

        $changes = [
            'before' => $before,
            'after' => $after,
            'changed_fields' => $changedFields,
        ];

        $this->recordModelAudit($model, 'UPDATE', 'SUCCESS', $changes);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->recordModelAudit($model, 'DELETE', 'SUCCESS');
    }

    /**
     * Dispatch structured audit record for the observed model.
     */
    private function recordModelAudit(Model $model, string $operation, string $result, ?array $changes = null): void
    {
        $modelClass = class_basename($model);
        $eventType = strtolower("{$modelClass}.{$operation}");

        $isDestructive = ($operation === 'DELETE');
        $severity = $isDestructive ? 'ALERT' : ($operation === 'CREATE' ? 'NOTICE' : 'INFORMATIONAL');

        $category = match ($modelClass) {
            'User', 'Team', 'TeamInvitation' => 'ACCESS_CONTROL',
            'PrivateKey', 'EnvironmentVariable' => 'SECRET_MGMT',
            'Server' => 'SYSTEM_INTEGRITY',
            default => 'RESOURCE_MGMT',
        };

        // Determine organization / tenant ID
        $organizationId = null;
        if (isset($model->team_id)) {
            $organizationId = $model->team_id;
        } elseif (isset($model->organization_id)) {
            $organizationId = $model->organization_id;
        } elseif ($modelClass === 'Team') {
            $organizationId = $model->id;
        } elseif (method_exists($model, 'team')) {
            try {
                $team = $model->team();
                $organizationId = is_object($team) ? data_get($team, 'id') : null;
            } catch (\Throwable) {
                $organizationId = null;
            }
        } elseif (method_exists($model, 'currentTeam')) {
            $organizationId = $model->currentTeam()?->id;
        }

        if (! $organizationId) {
            $organizationId = data_get($model, 'environment.project.team_id') ?? data_get($model, 'project.team_id');
        }

        $targetName = $model->name ?? ($model->description ?? ($model->key ?? $model->uuid ?? null));

        ForensicAuditService::record([
            'event_type' => $eventType,
            'event_category' => $category,
            'severity' => $severity,
            'action_operation' => $operation,
            'action_result' => $result,
            'organization_id' => $organizationId,
            'target_type' => $modelClass,
            'target_id' => (string) ($model->id ?? $model->uuid ?? 'unknown'),
            'target_name' => $targetName ? (string) $targetName : null,
            'changes' => $changes,
            'payload' => [
                'model_class' => get_class($model),
                'primary_key' => $model->getKey(),
            ],
        ]);
    }
}
