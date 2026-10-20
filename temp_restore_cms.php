<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // 1. Re-insert/restore roles in cms_landing
    DB::statement("DELETE FROM cms_landing.roles WHERE id IN (1, 2, 3)");
    DB::statement("INSERT INTO cms_landing.roles (id, name, guard_name, created_at, updated_at) VALUES 
        (1, 'super-admin', 'web', '2026-05-11 12:31:39', '2026-05-11 12:31:39'),
        (2, 'admin', 'web', '2026-05-11 12:31:39', '2026-05-11 12:31:39'),
        (3, 'user', 'web', '2026-05-11 12:31:39', '2026-05-11 12:31:39')
    ");

    // 2. Re-insert/restore users in cms_landing
    DB::statement("DELETE FROM cms_landing.users WHERE id IN (1, 2, 3)");
    DB::statement("INSERT INTO cms_landing.users (id, name, email, password, created_at, updated_at) VALUES 
        (1, 'Super Admin', 'superadmin@gmail.com', '$2y$12\$W.mcSCd4wB2eIZCwK64FI.QXFM8j915.X6VFTDLFDrLFJwtEKEk2W', '2026-05-11 12:31:39', '2026-05-11 12:31:39'),
        (2, 'reza', 'reza@gmail.com', '$2y$12\$Zp2dEM7lzDk1LuIq0TdHW.DTjd/ikw.08jPK7KAoJsGkNImIk/4Xu', '2026-05-11 16:27:57', '2026-05-11 16:27:57'),
        (3, 'Administrator', 'admin@admin.com', '$2y$12\$JW0uWa9sgXwL8R9Fy3Mc0OY3xtyaSMYiZz3N1J7jXMRIEG6cxw3s.', '2026-06-02 09:57:02', '2026-06-02 09:57:02')
    ");

    // 3. Re-insert model_has_roles relations in cms_landing
    DB::statement("DELETE FROM cms_landing.model_has_roles WHERE model_id IN (1, 2, 3)");
    DB::statement("INSERT INTO cms_landing.model_has_roles (role_id, model_type, model_id) VALUES 
        (1, 'App\\\\Models\\\\User', 1),
        (3, 'App\\\\Models\\\\User', 2),
        (2, 'App\\\\Models\\\\User', 3)
    ");

    echo "RESTORE SUCCESS\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
