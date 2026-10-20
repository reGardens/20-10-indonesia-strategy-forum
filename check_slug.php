<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sections = App\Models\Section::all(['id','slug','title'])->toArray();
foreach ($sections as $s) {
    echo $s['id'] . ' | ' . $s['slug'] . ' | ' . $s['title'] . "\n";
}
