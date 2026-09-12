<?php

namespace App\Livewire\Dashboard;

use App\Enums\ApplicationDeploymentStatus;
use App\Models\Application;
use App\Models\ApplicationDeploymentQueue;
use App\Models\Server;
use Illuminate\Support\Collection;
use Livewire\Component;

class ActiveDeployments extends Component
{
    public Collection $activeDeployments;

    public Collection $recentDeployments;

    public function mount(): void
    {
        $this->refreshDeployments();
    }

    public function refreshDeployments(): void
    {
        $columns = [
            'id',
            'application_id',
            'application_name',
            'deployment_url',
            'deployment_uuid',
            'pull_request_id',
            'server_name',
            'server_id',
            'status',
            'created_at',
            'finished_at',
        ];

        $baseQuery = ApplicationDeploymentQueue::query();

        if (currentTeam()?->id === 0) {
            $serverIds = Server::ownedByCurrentTeamCached()->pluck('id');
            if ($serverIds->isNotEmpty()) {
                $baseQuery->whereIn('server_id', $serverIds);
            }
        } else {
            $appIds = Application::ownedByCurrentTeam()->pluck('id')->map(fn ($id) => (string) $id);
            $baseQuery->whereIn('application_id', $appIds);
        }

        $this->activeDeployments = (clone $baseQuery)
            ->whereIn('status', [
                ApplicationDeploymentStatus::IN_PROGRESS->value,
                ApplicationDeploymentStatus::QUEUED->value,
            ])
            ->orderBy('status')
            ->orderBy('id')
            ->limit(5)
            ->get($columns);

        $this->recentDeployments = (clone $baseQuery)
            ->whereNotIn('status', [
                ApplicationDeploymentStatus::IN_PROGRESS->value,
                ApplicationDeploymentStatus::QUEUED->value,
            ])
            ->orderByDesc('id')
            ->limit(5)
            ->get($columns);
    }

    public function render()
    {
        return view('livewire.dashboard.active-deployments');
    }
}
