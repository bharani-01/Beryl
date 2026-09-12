<?php

namespace App\Livewire\Server;

use App\Models\Server;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class Index extends Component
{
    public ?Collection $servers = null;

    public function mount()
    {
        if (currentTeam()?->id !== 0) {
            return abort(403, 'Server management is restricted to instance administrators.');
        }

        $this->servers = Server::ownedByCurrentTeamCached();
    }

    public function render()
    {
        return view('livewire.server.index');
    }
}
