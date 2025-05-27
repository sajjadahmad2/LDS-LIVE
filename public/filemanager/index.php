<?php
// Load Laravel framework
require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Verify authentication AND authorization
// if (!auth()->check() ) {
//     abort(403, 'Unauthorized');
// }

// Prevent access to sensitive directories
$excludedPaths = [
    base_path('.env'),
    base_path('storage/framework/cache'),
    base_path('storage/framework/sessions'),
    base_path('storage/logs'),
];

$requestedPath = $_GET['p'] ?? '';
foreach ($excludedPaths as $path) {
    if (strpos($requestedPath, $path) === 0) {
        abort(403, 'Access to this directory is forbidden');
    }
}

// Continue with Tiny File Manager
require __DIR__.'/tinyfilemanager.php';
