<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            ['full_name' => 'Cheikh Ndiaye', 'email' => 'cheikh.ndiaye@ucak.sn'],
            ['full_name' => 'Aminata Diop', 'email' => 'aminata.diop@ucak.sn'],
            ['full_name' => 'Mamadou Ba', 'email' => 'mamadou.ba@ucak.sn'],
            ['full_name' => 'Fatou Sow', 'email' => 'fatou.sow@ucak.sn'],
        ];

        foreach ($admins as $admin) {
            $user = User::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'password' => bcrypt('password'),
                    'email_verified_at' => Carbon::now('Africa/Dakar'),
                    'remember_token' => Str::random(10),
                ]
            );
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('ADMIN');
            }

            Admin::firstOrCreate(
                ['user_id' => $user->id],
                ['full_name' => $admin['full_name']]
            );
        }
    }
}
