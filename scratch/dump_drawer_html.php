<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\ = \->make(Illuminate\\Contracts\\Console\\Kernel::class);
\->bootstrap();

use App\\Models\\User;
use App\\Livewire\\Admin\\Index as AdminIndex;
use Livewire\\Livewire;

auth()->login(User::find(0));
\ = Livewire::test(AdminIndex::class);
\->call('inspectUserResources', 1);
\->call('setDrawerTab', 'resources');
echo substr(\->html(), strpos(\->html(), 'Applications ('), 1200);
