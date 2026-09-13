@php
    $indexPath = public_path('landing/index.html');
    if (file_exists($indexPath)) {
        echo file_get_contents($indexPath);
    } else {
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0; url=/login"></head><body>Redirecting to login...</body></html>';
    }
@endphp
