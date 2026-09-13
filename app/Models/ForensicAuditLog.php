<?php

namespace App\Models;

use App\Exceptions\SecurityException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForensicAuditLog extends Model
{
    protected $table = 'forensic_audit_logs';

    protected $guarded = [];

    protected $casts = [
        'event_time' => 'datetime',
        'received_at' => 'datetime',
        'persisted_at' => 'datetime',
        'actor' => 'array',
        'target' => 'array',
        'action' => 'array',
        'request' => 'array',
        'changes' => 'array',
        'authentication' => 'array',
        'source' => 'array',
        'security' => 'array',
        'deployment_provenance' => 'array',
    ];

    /**
     * Enforce immutability: updates to existing audit records are strictly prohibited.
     *
     * @throws SecurityException
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new SecurityException('SECURITY VIOLATION: Forensic audit logs are append-only. Modification of existing records is strictly prohibited.');
        }

        return parent::save($options);
    }

    /**
     * Enforce immutability: deletion of audit records is strictly prohibited.
     *
     * @throws SecurityException
     */
    public function delete(): ?bool
    {
        throw new SecurityException('SECURITY VIOLATION: Forensic audit logs are immutable. Deletion of records is strictly prohibited.');
    }

    /**
     * Scope query to a specific organization / tenant.
     */
    public function scopeForTenant(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope query to a specific operation lineage.
     */
    public function scopeForOperation(Builder $query, string $operationId): Builder
    {
        return $query->where('operation_id', $operationId);
    }

    /**
     * Scope query by category.
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('event_category', $category);
    }

    /**
     * Scope query by severity.
     */
    public function scopeSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Relation to the organization / team.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'organization_id');
    }
}
