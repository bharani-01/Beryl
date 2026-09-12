<?php

/**
 * Audit Logs navigation and actions must use the relatable "audit-logs" icon
 * (clipboard with checklist/ledger lines), not the unrelated Mac command "terminal" glyph.
 */
test('audit logs navigation and admin views use relatable audit-logs icon', function () {
    $navbar = file_get_contents(resource_path('views/components/navbar.blade.php'));
    $admin = file_get_contents(resource_path('views/livewire/admin/index.blade.php'));
    $reicon = file_get_contents(resource_path('views/components/reicon.blade.php'));

    // Navbar uses audit-logs icon
    expect($navbar)
        ->toContain('<x-reicon name="audit-logs" class="menu-item-icon" />')
        ->not->toMatch('/title="Audit Logs"[\s\S]{0,150}?<x-reicon name="terminal"/');

    // Admin index uses audit-logs icon for security audit logs button and inspect user action
    expect($admin)
        ->toContain('<x-reicon name="audit-logs" class="size-3.5" />')
        ->not->toContain('<x-reicon name="terminal"');

    // Reicon pack includes audit-logs definition
    expect($reicon)
        ->toContain("'audit-logs' =>")
        ->toContain('<rect x="8" y="2"')
        ->toContain("'audit' =>");
});
