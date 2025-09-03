<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([ AdminUserSeeder::class ]);
        // Hapus admin lama dengan email ini (opsional)
        User::where('email', 'admin@example.com')->delete();

        User::create([
            'name'      => 'Administrator',
            'email'     => 'admin@example.com',
            'password'  => Hash::make('password'),  // ganti setelah login!
            'role'      => 'admin',
            'is_active' => 1,
        ]);
    }
}
