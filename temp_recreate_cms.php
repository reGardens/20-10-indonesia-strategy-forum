<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // 1. Create database if not exists
    DB::statement("CREATE DATABASE IF NOT EXISTS cms_landing");
    echo "Database cms_landing created or already exists.\n";

    // 2. Run migrations for topicare project
    $output = [];
    $retval = null;
    exec("php C:\\laragon\\www\\topicare\\artisan migrate:fresh", $output, $retval);
    echo "Migration output:\n" . implode("\n", $output) . "\n";

    if ($retval !== 0) {
        throw new \Exception("Migration failed with code $retval");
    }

    // 3. Restore roles
    DB::statement("INSERT INTO cms_landing.roles (id, name, guard_name, created_at, updated_at) VALUES 
        (1, 'super-admin', 'web', '2026-05-11 12:31:39', '2026-05-11 12:31:39'),
        (2, 'admin', 'web', '2026-05-11 12:31:39', '2026-05-11 12:31:39'),
        (3, 'user', 'web', '2026-05-11 12:31:39', '2026-05-11 12:31:39')
    ");

    // 4. Restore users
    DB::statement("INSERT INTO cms_landing.users (id, name, email, password, created_at, updated_at) VALUES 
        (1, 'Super Admin', 'superadmin@gmail.com', '$2y$12\$W.mcSCd4wB2eIZCwK64FI.QXFM8j915.X6VFTDLFDrLFJwtEKEk2W', '2026-05-11 12:31:39', '2026-05-11 12:31:39'),
        (2, 'reza', 'reza@gmail.com', '$2y$12\$Zp2dEM7lzDk1LuIq0TdHW.DTjd/ikw.08jPK7KAoJsGkNImIk/4Xu', '2026-05-11 16:27:57', '2026-05-11 16:27:57'),
        (3, 'Administrator', 'admin@admin.com', '$2y$12\$JW0uWa9sgXwL8R9Fy3Mc0OY3xtyaSMYiZz3N1J7jXMRIEG6cxw3s.', '2026-06-02 09:57:02', '2026-06-02 09:57:02')
    ");

    // 5. Restore model_has_roles
    DB::statement("INSERT INTO cms_landing.model_has_roles (role_id, model_type, model_id) VALUES 
        (1, 'App\\\\Models\\\\User', 1),
        (3, 'App\\\\Models\\\\User', 2),
        (2, 'App\\\\Models\\\\User', 3)
    ");

    echo "DATABASE cms_landing FULLY RESTORED WITH ORIGINAL TABLES AND DATA!\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
