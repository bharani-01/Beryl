<?php
require 'vendor/autoload.php';
 = require_once 'bootstrap/app.php';
 = ->make(Illuminate\\Contracts\\Console\\Kernel::class);
->bootstrap();

 = App\\Models\\User::find(0);
auth()->login();
session(['currentTeam' => App\\Models\\Team::find(0)]);

 = app(App\\Livewire\\Admin\\Index::class);
->mount();
 = App\\Models\\ForensicAuditLog::latest('sequence_number')->first();
->viewForensicEvent(->event_id);

 = ->render();
 = ->render();
echo 'MODAL RENDER SUCCESS! HTML SIZE: ' . strlen() . PHP_EOL;
echo 'HAS ESC LISTENER: ' . (str_contains(, 'closeForensicModal()') ? 'YES' : 'NO') . PHP_EOL;
echo 'HAS STICKY HEADER: ' . (str_contains(, 'Sticky Header') ? 'YES' : 'NO') . PHP_EOL;
echo 'HAS STICKY FOOTER: ' . (str_contains(, 'Close Forensic Inspector') ? 'YES' : 'NO') . PHP_EOL;
echo 'HAS ESC HINT: ' . (str_contains(, 'Press') && str_contains(, 'Esc') ? 'YES' : 'NO') . PHP_EOL;
