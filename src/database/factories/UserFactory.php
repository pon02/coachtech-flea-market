<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $prefectures = [
            '東京都',
        ];
        
        $towns = [
            '新宿区西新宿', '渋谷区神宮前', '中央区銀座', '千代田区神田', '港区六本木', '台東区浅草', '品川区大井町', '豊島区池袋'
        ];
        
        $buildings = [
            'パークマンション', 'グランドハイツ', 'ロイヤルコート', 'エクセレント',
            'プライムタワー', 'ガーデンハウス', 'メゾン', 'ヴィラ'
        ];

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
            'postal_code' => $this->faker->numerify('###-####'),
            'address' => $this->faker->randomElement($prefectures) . 
                        $this->faker->randomElement($towns) . 
                        $this->faker->numberBetween(1, 5) . '-' . 
                        $this->faker->numberBetween(1, 20) . '-' . 
                        $this->faker->numberBetween(1, 30),
            'building' => $this->faker->randomElement($buildings) . 
                         $this->faker->numberBetween(101, 999),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
