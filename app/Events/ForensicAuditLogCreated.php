<?php

namespace App\Events;

use App\Models\ForensicAuditLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ForensicAuditLogCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $log;

    public ?int $organizationId = null;

    public function __construct(ForensicAuditLog $auditLog)
    {
        $this->organizationId = $auditLog->organization_id ? (int) $auditLog->organization_id : null;

        $this->log = [
            'id' => $auditLog->id,
            'event_id' => $auditLog->event_id,
            'sequence_number' => $auditLog->sequence_number,
            'operation_id' => $auditLog->operation_id,
            'parent_event_id' => $auditLog->parent_event_id,
            'root_event_id' => $auditLog->root_event_id,
            'event_type' => $auditLog->event_type,
            'event_category' => $auditLog->event_category,
            'severity' => $auditLog->severity,
            'action_operation' => $auditLog->action_operation,
            'action_result' => $auditLog->action_result,
            'action_reason' => $auditLog->action_reason,
            'actor_type' => $auditLog->actor_type,
            'actor_id' => $auditLog->actor_id,
            'actor_email' => $auditLog->actor_email,
            'source_type' => $auditLog->source_type,
            'organization_id' => $this->organizationId,
            'environment_name' => $auditLog->environment_name,
            'target_type' => $auditLog->target_type,
            'target_id' => $auditLog->target_id,
            'target_name' => $auditLog->target_name,
            'ip_address' => $auditLog->ip_address,
            'event_time' => $auditLog->event_time?->toISOString(),
            'event_hash' => $auditLog->event_hash,
            'actor' => $auditLog->actor,
            'target' => $auditLog->target,
            'changes' => $auditLog->changes,
        ];
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('team.0'), // Platform instance administrators always receive
        ];

        if ($this->organizationId && $this->organizationId !== 0) {
            $channels[] = new PrivateChannel("team.{$this->organizationId}");
        }

        return $channels;
    }
}
