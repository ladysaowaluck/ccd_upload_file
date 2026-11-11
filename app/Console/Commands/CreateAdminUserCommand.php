<?php

namespace App\Console\Commands;

use App\Models\Auth\AdminPermission;
use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin-user 
                            {username? : Username for the admin user}
                            {email? : Email address for the admin user}
                            {--password= : Plain-text password (defaults to password123 if not provided)}
                            {--name= : Full display name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or elevate an admin user with full system permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = $this->argument('username') ?: $this->ask('Enter username', 'admin');
        $email = $this->argument('email') ?: $this->ask('Enter corporate email', "{$username}@th.nissin-asia.com");
        $password = $this->option('password') ?: $this->secret('Enter password (leave blank for default "Admin@1234")');
        if (empty($password)) {
            $password = 'Admin@1234';
        }
        $name = $this->option('name') ?: ($this->argument('username') ? ucfirst($username) : $this->ask('Enter display name', 'Super Administrator'));

        $this->info("Setting up administrator user: [{$username}] ({$email})...");

        // 1. Ensure administrator role exists
        $adminRole = AdminRole::firstOrCreate(
            ['slug' => 'administrator'],
            ['name' => 'Administrator']
        );

        // 2. Attach all available permissions to the administrator role
        $allPermissionIds = AdminPermission::pluck('id')->toArray();
        if (!empty($allPermissionIds)) {
            $adminRole->permissions()->sync($allPermissionIds);
            $this->info("Synchronized " . count($allPermissionIds) . " system permissions to role [{$adminRole->name}].");
        }

        // 3. Find or create the AdminUser
        $user = AdminUser::where('username', $username)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $this->warn("User [{$user->username}] already exists. Elevating permissions and resetting password...");
            $user->update([
                'name' => $name ?: $user->name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        } else {
            $user = AdminUser::create([
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'avatar' => null,
                'office_id' => 1,
                'department_id' => 1,
                'division_id' => 1,
            ]);
            $this->info("Created new user [{$user->username}].");
        }

        // 4. Attach administrator role and all other roles so they have every permission
        $allRoleIds = AdminRole::pluck('id')->toArray();
        $user->roles()->sync($allRoleIds);

        $this->newLine();
        $this->info("=================================================");
        $this->info(" SUCCESS: Super Admin user is ready!");
        $this->info(" Username : {$user->username}");
        $this->info(" Email    : {$user->email}");
        $this->info(" Password : {$password}");
        $this->info(" Roles    : " . $user->roles()->pluck('name')->implode(', '));
        $this->info(" Can do everything: YES (isAdmin = true)");
        $this->info("=================================================");

        return Command::SUCCESS;
    }
}

