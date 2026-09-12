<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(0);
auth()->login($user);

$component = Livewire\Livewire::test(App\Livewire\Admin\Index::class);
$html = $component->html();

file_put_contents('/tmp/admin_rendered.html', $html);
echo "HTML dumped to /tmp/admin_rendered.html (" . strlen($html) . " bytes)\n";
