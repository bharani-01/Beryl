<?php

namespace App\Livewire\SharedVariables;

use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        if (! isInstanceAdmin()) {
            abort(403, 'Access to shared variables is restricted to instance administrators');
        }
    }

    public function render()
    {
        return view('livewire.shared-variables.index');
    }
}
