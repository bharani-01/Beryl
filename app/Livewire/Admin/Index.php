<?php

namespace App\Livewire\Admin;

use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public int $activeSubscribers = 0;

    public int $inactiveSubscribers = 0;

    public int $totalServers = 0;

    public int $activeServers = 0;

    public int $totalUsers = 0;

    public int $totalTeams = 0;

    public Collection $servers;

    public Collection $foundUsers;

    public string $search = '';

    public function mount()
    {
        $this->authorizeAdminAccess();
        $this->loadFleetStats();
        $this->getSubscribers();
        $this->loadUsers();
    }

    public function loadFleetStats(): void
    {
        $this->servers = Server::where(function ($query) {
            $query->where('team_id', 0)->orWhere('id', 0);
        })
            ->with(['settings'])
            ->orderBy('id')
            ->get();

        $this->totalServers = $this->servers->count();
        $this->activeServers = $this->servers->filter(function ($server) {
            return (bool) data_get($server, 'settings.is_reachable', false);
        })->count();

        $this->totalUsers = User::count();
        $this->totalTeams = Team::where('id', '!=', 0)->count();
    }

    public function loadUsers(): void
    {
        if ($this->search !== '') {
            $this->foundUsers = User::where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            })->with('teams')->latest()->take(50)->get();
        } else {
            $this->foundUsers = User::with('teams')->latest()->take(30)->get();
        }
    }

    public function back()
    {
        $this->authorizeAdminAccess();
        if (session('impersonating')) {
            session()->forget('impersonating');
            $user = User::find(0);
            $team_to_switch_to = $user->resolveStoredTeam() ?? $user->teams->first();
            Auth::login($user);
            refreshSession($team_to_switch_to);

            return redirect()->route('admin.index');
        }
    }

    public function submitSearch()
    {
        $this->authorizeAdminAccess();
        $this->loadUsers();
    }

    public function getSubscribers()
    {
        if (Auth::id() !== 0 && ! isInstanceAdmin() && ! session('impersonating')) {
            abort(403);
        }
        $this->inactiveSubscribers = Team::whereRelation('subscription', 'stripe_invoice_paid', false)->count();
        $this->activeSubscribers = Team::whereRelation('subscription', 'stripe_invoice_paid', true)->count();
    }

    public function switchUser(int $user_id)
    {
        $this->authorizeRootOnly();
        session(['impersonating' => true]);
        $user = User::find($user_id);
        if (! $user) {
            abort(404);
        }
        $team_to_switch_to = $user->resolveStoredTeam() ?? $user->teams->first();
        Auth::login($user);
        refreshSession($team_to_switch_to);

        return redirect()->route('dashboard');
    }

    private function authorizeAdminAccess(): void
    {
        if (! Auth::check() || (! isInstanceAdmin() && Auth::id() !== 0 && ! session('impersonating'))) {
            abort(403, 'Unauthorized access to admin panel');
        }
    }

    private function authorizeRootOnly(): void
    {
        if (! Auth::check() || (! isInstanceAdmin() && Auth::id() !== 0)) {
            abort(403, 'Unauthorized access to admin panel');
        }
    }

    public function render()
    {
        return view('livewire.admin.index');
    }
}
