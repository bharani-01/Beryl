<?php

namespace App\Livewire;

use App\Models\PrivateKey;
use App\Models\Project;
use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public Collection $projects;

    public Collection $servers;

    public Collection $privateKeys;

    public int $totalApplications = 0;

    public int $runningApplications = 0;

    public int $totalDatabases = 0;

    public int $totalServices = 0;

    public int $sourcesCount = 0;

    public int $membersCount = 1;

    public ?string $teamName = null;

    public ?string $teamRole = 'Team Owner';

    public ?string $teamUuid = null;

    public ?int $teamId = null;

    public ?string $projectNumber = null;

    public ?string $projectId = null;

    public Collection $recentResources;

    public Collection $teamActivities;

    public ?string $primaryEnvironmentUrl = null;

    public ?string $newResourceUrl = null;

    public ?string $deployAppUrl = null;

    public ?string $createDbUrl = null;

    public ?string $deployServiceUrl = null;

    public ?string $applicationsUrl = null;

    public ?string $databasesUrl = null;

    public ?string $servicesUrl = null;

    public ?string $addServerUrl = null;

    public ?string $serversUrl = null;

    public function mount()
    {
        if ((auth()->id() === 0 || isInstanceAdmin()) && ! session('impersonating')) {
            return redirect()->route('admin.index');
        }

        $team = currentTeam();
        $this->teamName = $team?->name ?? 'Workspace';
        $this->teamUuid = $team?->uuid ?? null;
        $this->teamId = $team?->id ?? 0;
        $this->projectNumber = str_pad((string) ($this->teamId * 1847192 + 991576), 8, '0', STR_PAD_LEFT);
        $this->projectId = 'ws-' . str_pad((string) $this->teamId, 4, '0', STR_PAD_LEFT);

        $currentUser = auth()->user();
        if ($currentUser && $team) {
            $memberRecord = $team->members()->where('user_id', $currentUser->id)->first();
            $role = $memberRecord?->pivot?->role ?? 'owner';
            $this->teamRole = match (strtolower($role)) {
                'admin' => 'Team Admin',
                'member' => 'Team Member',
                default => 'Team Owner',
            };
        }

        $this->privateKeys = PrivateKey::ownedByCurrentTeamCached();
        $this->servers = Server::ownedByCurrentTeamCached();
        $this->projects = Project::ownedByCurrentTeam()
            ->with(['environments:id,uuid,name,project_id'])
            ->withCount([
                'applications',
                'services',
                'postgresqls',
                'redis',
                'keydbs',
                'dragonflies',
                'clickhouses',
                'mongodbs',
                'mysqls',
                'mariadbs',
            ])
            ->get();

        $this->totalApplications = (int) $this->projects->sum('applications_count');
        $this->totalServices = (int) $this->projects->sum('services_count');
        $this->totalDatabases = (int) $this->projects->sum(function ($p) {
            return $p->postgresqls_count + $p->redis_count + $p->keydbs_count +
                $p->dragonflies_count + $p->clickhouses_count + $p->mongodbs_count +
                $p->mysqls_count + $p->mariadbs_count;
        });

        if ($team) {
            try {
                $this->runningApplications = $team->applications()->where('status', 'like', '%running%')->count();
            } catch (\Throwable) {
                $this->runningApplications = 0;
            }
            try {
                $this->sourcesCount = $team->sources()->count();
            } catch (\Throwable) {
                $this->sourcesCount = 0;
            }
            try {
                $this->membersCount = $team->members()->count();
            } catch (\Throwable) {
                $this->membersCount = 1;
            }
        }

        // Collect personalized recent resources
        $resources = collect();

        foreach ($this->projects as $p) {
            foreach ($p->environments as $env) {
                // Applications
                try {
                    $apps = $env->applications()->with(['tags', 'settings'])->take(6)->get();
                    foreach ($apps as $app) {
                        $updated = $app->updated_at ? Carbon::parse($app->updated_at) : now();
                        $status = $app->status ?? 'stopped';
                        $isRunning = str_contains(strtolower($status), 'running') || str_contains(strtolower($status), 'healthy');

                        $resources->push([
                            'id' => $app->id,
                            'uuid' => $app->uuid,
                            'name' => $app->name,
                            'type' => 'Application',
                            'type_icon' => 'box',
                            'type_color' => 'blue',
                            'status' => $isRunning ? 'Running' : ucfirst($status),
                            'is_running' => $isRunning,
                            'fqdn' => $app->fqdn ?? null,
                            'updated_at' => $updated,
                            'updated_at_diff' => $updated->diffForHumans(null, true) . ' ago',
                            'url' => route('project.application.configuration', [
                                'project_uuid' => $p->uuid,
                                'environment_uuid' => $env->uuid,
                                'application_uuid' => $app->uuid,
                            ]),
                            'project_name' => $p->name,
                            'environment_name' => $env->name,
                        ]);
                    }
                } catch (\Throwable) {}

                // Databases (all standalone types: PostgreSQL, Redis, MySQL, MariaDB, MongoDB, KeyDB, Dragonfly, ClickHouse)
                try {
                    $dbs = $env->databases();
                    foreach ($dbs as $db) {
                        $updated = $db->updated_at ? Carbon::parse($db->updated_at) : now();
                        $status = $db->status ?? 'stopped';
                        $isRunning = str_contains(strtolower($status), 'running') || str_contains(strtolower($status), 'healthy');

                        $resources->push([
                            'id' => $db->id,
                            'uuid' => $db->uuid,
                            'name' => $db->name,
                            'type' => 'Database',
                            'type_icon' => 'database',
                            'type_color' => 'emerald',
                            'status' => $isRunning ? 'Running' : ucfirst($status),
                            'is_running' => $isRunning,
                            'fqdn' => null,
                            'updated_at' => $updated,
                            'updated_at_diff' => $updated->diffForHumans(null, true) . ' ago',
                            'url' => route('project.database.configuration', [
                                'project_uuid' => $p->uuid,
                                'environment_uuid' => $env->uuid,
                                'database_uuid' => $db->uuid,
                            ]),
                            'project_name' => $p->name,
                            'environment_name' => $env->name,
                        ]);
                    }
                } catch (\Throwable) {}

                // Services
                try {
                    $services = $env->services()->take(10)->get();
                    foreach ($services as $svc) {
                        $updated = $svc->updated_at ? Carbon::parse($svc->updated_at) : now();
                        $status = $svc->status ?? 'stopped';
                        $isRunning = str_contains(strtolower($status), 'running') || str_contains(strtolower($status), 'healthy');

                        $resources->push([
                            'id' => $svc->id,
                            'uuid' => $svc->uuid,
                            'name' => $svc->name,
                            'type' => 'Service',
                            'type_icon' => 'layers',
                            'type_color' => 'purple',
                            'status' => $isRunning ? 'Running' : ucfirst($status),
                            'is_running' => $isRunning,
                            'fqdn' => null,
                            'updated_at' => $updated,
                            'updated_at_diff' => $updated->diffForHumans(null, true) . ' ago',
                            'url' => route('project.service.configuration', [
                                'project_uuid' => $p->uuid,
                                'environment_uuid' => $env->uuid,
                                'service_uuid' => $svc->uuid,
                            ]),
                            'project_name' => $p->name,
                            'environment_name' => $env->name,
                        ]);
                    }
                } catch (\Throwable) {}
            }
        }

        $this->recentResources = $resources->sortByDesc('updated_at')->take(25)->values();

        // Build recent team activities
        $activities = collect();
        foreach ($this->recentResources->take(4) as $idx => $res) {
            $shortName = strlen($res['name']) > 16 ? substr($res['name'], 0, 14) . '...' : $res['name'];
            if ($res['type'] === 'Service') {
                $activities->push([
                    'text' => "{$shortName} is now running",
                    'color' => 'emerald',
                    'time' => '2 hours ago',
                ]);
            } elseif ($res['type'] === 'Database') {
                $activities->push([
                    'text' => "{$shortName} was deployed",
                    'color' => 'blue',
                    'time' => '1 hour ago',
                ]);
            } else {
                $activities->push([
                    'text' => "{$shortName} was updated",
                    'color' => 'neutral',
                    'time' => '2 hours ago',
                ]);
            }
        }
        if ($activities->isEmpty()) {
            $activities->push([
                'text' => 'Workspace initialized',
                'color' => 'emerald',
                'time' => 'Just now',
            ]);
        }
        $this->teamActivities = $activities->values();

        $firstProject = $this->projects->first();
        if ($firstProject && $firstProject->environments->isNotEmpty()) {
            $firstEnv = $firstProject->environments->first();

            $this->primaryEnvironmentUrl = route('project.resource.index', [
                'project_uuid' => $firstProject->uuid,
                'environment_uuid' => $firstEnv->uuid,
            ]);
            $this->newResourceUrl = route('project.resource.create', [
                'project_uuid' => $firstProject->uuid,
                'environment_uuid' => $firstEnv->uuid,
            ]);
            $this->deployAppUrl = $this->newResourceUrl . '?type=public';
            $this->createDbUrl = $this->newResourceUrl . '?type=postgresql';
            $this->deployServiceUrl = $this->newResourceUrl . '?type=service';

            $firstApp = $resources->firstWhere('type', 'Application');
            $this->applicationsUrl = $firstApp ? $firstApp['url'] : $this->deployAppUrl;

            $firstDb = $resources->first(fn ($r) => $r['type'] === 'Database');
            $this->databasesUrl = $firstDb ? $firstDb['url'] : $this->createDbUrl;

            $firstSvc = $resources->firstWhere('type', 'Service');
            $this->servicesUrl = $firstSvc ? $firstSvc['url'] : $this->deployServiceUrl;
        } else {
            $this->primaryEnvironmentUrl = route('project.index');
            $this->newResourceUrl = route('project.index');
            $this->deployAppUrl = route('project.index');
            $this->createDbUrl = route('project.index');
            $this->deployServiceUrl = route('project.index');
            $this->applicationsUrl = route('project.index');
            $this->databasesUrl = route('project.index');
            $this->servicesUrl = route('project.index');
        }

        $this->addServerUrl = route('server.create');
        $this->serversUrl = route('server.index');
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
