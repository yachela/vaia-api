<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature   = 'admin:create';
    protected $description = 'Crea un usuario administrador desde variables de entorno ADMIN_EMAIL y ADMIN_PASSWORD';

    public function handle(): int
    {
        $email    = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        $name     = env('ADMIN_NAME', 'Admin');

        if (empty($email) || empty($password)) {
            $this->error('Definí ADMIN_EMAIL y ADMIN_PASSWORD como variables de entorno.');
            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->info("El usuario {$email} ya existe.");
            return self::SUCCESS;
        }

        User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->info("Usuario admin {$email} creado correctamente.");
        return self::SUCCESS;
    }
}
