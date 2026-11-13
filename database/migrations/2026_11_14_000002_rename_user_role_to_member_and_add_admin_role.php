<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Issue #42: role `user` → `member` (Membership ISF), tambah role `admin` (Operasional CMS).
     * Rename di baris yang sama (id tetap) → semua user yang punya role `user` otomatis jadi `member`.
     */
    public function up(): void
    {
        $now = now();
        $memberExists = DB::table('roles')->where('name', 'member')->where('guard_name', 'web')->exists();

        if (! $memberExists) {
            DB::table('roles')->where('name', 'user')->where('guard_name', 'web')
                ->update(['name' => 'member', 'updated_at' => $now]);
        }

        foreach (['member', 'admin'] as $name) {
            if (! DB::table('roles')->where('name', $name)->where('guard_name', 'web')->exists()) {
                DB::table('roles')->insert(['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $now = now();
        if (! DB::table('roles')->where('name', 'user')->where('guard_name', 'web')->exists()) {
            DB::table('roles')->where('name', 'member')->where('guard_name', 'web')
                ->update(['name' => 'user', 'updated_at' => $now]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
