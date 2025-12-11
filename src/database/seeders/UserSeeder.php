<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'tanaka',
                'email' => 'tanaka@example.com',
                'password' => Hash::make('12345678'),
                'profile_image' => 'profile_images/tanaka.jpeg',
            ],
            [
                'name' => 'yamada',
                'email' => 'yamada@example.com',
                'password' => Hash::make('12345678'),
                'profile_image' => 'profile_images/yamada.jpeg',
            ],
            [
                'name' => 'suzuki',
                'email' => 'suzuki@example.com',
                'password' => Hash::make('12345678'),
                'profile_image' => 'profile_images/suzuki.jpeg',
            ],
            [
                'name' => 'sato',
                'email' => 'sato@example.com',
                'password' => Hash::make('12345678'),
                'profile_image' => 'profile_images/sato.jpeg',
            ],
            [
                'name' => 'takeda',
                'email' => 'takeda@example.com',
                'password' => Hash::make('12345678'),
                'profile_image' => 'profile_images/takeda.jpeg',
            ],
        ];

        foreach ($users as $userData) {
            // Factoryからaddressとbuildingのデータを取得
            $factoryData = User::factory()->make();
            
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'email_verified_at' => now(),
                'postal_code' => $factoryData->postal_code,
                'address' => $factoryData->address,
                'building' => $factoryData->building,
                'profile_image' => $userData['profile_image'],
            ]);
        }
    }
}
