<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Condition;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 * 
 * ⚠️ このファクトリーはテスト専用です
 * 本番環境での使用は想定されていません
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     * テスト用の商品データを生成
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(rand(1, 3), true),
            'brand_name' => $this->faker->optional()->company(),
            'condition_id' => function () {
                return Condition::inRandomOrder()->first()->id ?? 1;
            },
            'description' => $this->faker->sentence(rand(5, 15)),
            'price' => $this->faker->numberBetween(100, 50000),
            'item_image' => 'test_items/' . $this->faker->word() . '.jpg',
            'is_sold' => false,
        ];
    }

    public function sold()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_sold' => true,
            ];
        });
    }

    public function ownedBy(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }
}
