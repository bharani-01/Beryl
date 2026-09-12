<?php

namespace App\Livewire\SharedVariables\Server;

use App\Models\Server;
use Illuminate\Support\Collection;
use Livewire\Component;

class Index extends Component
{
    public Collection $servers;

    public function mount()
    {
        if (currentTeam()?->id !== 0) {
            abort(403);
        }

        $this->servers = Server::ownedByCurrentTeamCached();
    }

    public function render()
    {
        return view('livewire.shared-variables.server.index');
    }
}
