<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the core roles and the initial super admin account.
     *
     * Credentials come from SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD in .env.
     * If no password is set, a random one is generated and printed once.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Role names used by RoleController / UserController
        foreach (['super-admin', 'user'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $email = env('SUPERADMIN_EMAIL', 'superadmin@gmail.com');
        $password = env('SUPERADMIN_PASSWORD');
        $generated = false;

        if (empty($password)) {
            $password = Str::password(16, symbols: false);
            $generated = true;
        }

        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;

        if ($isNew) {
            $user->name = 'Super Admin';
            $user->password = Hash::make($password);
            $user->email_verified_at = now();
            $user->save();
        } elseif ($user->trashed()) {
            $user->restore();
        }

        $user->syncRoles(['super-admin']);

        if ($isNew && $this->command) {
            $this->command->info("Super admin: {$email}");
            if ($generated) {
                $this->command->warn("Generated password (shown once): {$password}");
            }
        }

        // ISF public content (sample data shown on Beranda, Tokoh, Isu, Anggota, Email Briefing)
        $this->call(IsfContentSeeder::class);
    }
}
