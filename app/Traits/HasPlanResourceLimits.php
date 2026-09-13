<?php

namespace App\Traits;

trait HasPlanResourceLimits
{
    /**
     * Boot the trait to assign team subscription resource limits on creation.
     */
    protected static function bootHasPlanResourceLimits(): void
    {
        static::creating(function ($model): void {
            $team = currentTeam() ?? $model->environment?->project?->team;
            if ($team && $team->id !== 0 && function_exists('teamResourceLimits')) {
                $limits = teamResourceLimits($team);
                if (empty($model->limits_cpus) || $model->limits_cpus === '0') {
                    $model->limits_cpus = (string) $limits['cpus'];
                }
                if (empty($model->limits_memory) || $model->limits_memory === '0') {
                    $model->limits_memory = (string) $limits['memory'];
                }
            }
        });
    }
}
