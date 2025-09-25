<?php
namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;


class AdminUserSeeder extends Seeder {
    public function run(): void {
        $password = Str::random(14); // сгенерируем
        $user = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'phone' => '+7 700 000 00 00',
                'password' => bcrypt($password),
            ]
        );
        if (!$user->hasRole('Admin')) $user->assignRole('Admin');


// Выведем пароль в консоль при сидинге
        $this->command->warn("\nAdmin логин: admin@example.com");
        $this->command->warn("Admin пароль: {$password}\n");
    }
}
