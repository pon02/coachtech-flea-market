<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserSeeder extends Seeder
{
    private function ensurePublicFileExists(string $destPath, string $sourcePath): void
    {
        $disk = Storage::disk('public');
        if ($disk->exists($destPath)) {
            return;
        }

        if (!is_file($sourcePath)) {
            return;
        }

        $contents = file_get_contents($sourcePath);
        if ($contents === false) {
            return;
        }

        $disk->put($destPath, $contents);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->ensurePublicFileExists(
            'profile_images/tanaka.jpeg',
            database_path('seeders/assets/profile_images/tanaka.jpeg')
        );
        $this->ensurePublicFileExists(
            'profile_images/yamada.jpeg',
            database_path('seeders/assets/profile_images/yamada.jpeg')
        );
        $this->ensurePublicFileExists(
            'profile_images/suzuki.jpeg',
            database_path('seeders/assets/profile_images/suzuki.jpeg')
        );

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
