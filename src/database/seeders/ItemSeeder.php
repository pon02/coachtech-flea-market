<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $watch = Item::create([
            'user_id' => 1,
            'name' => '腕時計',
            'brand_name' => 'Rolax',
            'condition_id' => 1,
            'description' => 'スタイリッシュなデザインのメンズ腕時計',
            'price' => 15000,
            'item_image' => 'items/MensClock.jpg',
        ]);
        $watch->categories()->attach([1, 5, 12]);

        $hdd = Item::create([
            'user_id' => 1,
            'name' => 'HDD',
            'brand_name' => '西芝',
            'condition_id' => 2,
            'description' => '高速で信頼性の高いハードディスク',
            'price' => 5000,
            'item_image' => 'items/HDDHardDisk.jpg',
        ]);
        $hdd->categories()->attach([2]);

        $onion = Item::create([
            'user_id' => 1,
            'name' => '玉ねぎ3束',
            'brand_name' => 'なし',
            'condition_id' => 3,
            'description' => '新鮮な玉ねぎ3束のセット',
            'price' => 300,
            'item_image' => 'items/Tamanegi.jpg',
        ]);
        $onion->categories()->attach([10, 11]);

        $shoes = Item::create([
            'user_id' => 1,
            'name' => '革靴',
            'brand_name' => '',
            'condition_id' => 4,
            'description' => 'クラシックなデザインの革靴',
            'price' => 4000,
            'item_image' => 'items/LeatherShoes.jpg',
        ]);
        $shoes->categories()->attach([1, 5]);

        $laptop = Item::create([
            'user_id' => 1,
            'name' => 'ノートPC',
            'brand_name' => '',
            'condition_id' => 1,
            'description' => '高性能なノートパソコン',
            'price' => 45000,
            'item_image' => 'items/NotePC.jpg',
        ]);
        $laptop->categories()->attach([2]);

        $mic = Item::create([
            'user_id' => 2,
            'name' => 'マイク',
            'brand_name' => 'なし',
            'condition_id' => 2,
            'description' => '高音質のレコーディング用マイク',
            'price' => 8000,
            'item_image' => 'items/Mic.jpg',
        ]);
        $mic->categories()->attach([2]);

        $bag = Item::create([
            'user_id' => 2,
            'name' => 'ショルダーバッグ',
            'brand_name' => '',
            'condition_id' => 3,
            'description' => 'おしゃれなショルダーバッグ',
            'price' => 3500,
            'item_image' => 'items/Bag.jpg',
        ]);
        $bag->categories()->attach([1, 4]);

        $tumbler = Item::create([
            'user_id' => 2,
            'name' => 'タンブラー',
            'brand_name' => 'なし',
            'condition_id' => 4,
            'description' => '使いやすいタンブラー',
            'price' => 500,
            'item_image' => 'items/Tumbler.jpg',
        ]);
        $tumbler->categories()->attach([10]);

        $grinder = Item::create([
            'user_id' => 2,
            'name' => 'コーヒーミル',
            'brand_name' => 'Starbacks',
            'condition_id' => 1,
            'description' => '手動のコーヒーミル',
            'price' => 4000,
            'item_image' => 'items/CoffeeMill.jpg',
        ]);
        $grinder->categories()->attach([10]);

        $makeup = Item::create([
            'user_id' => 2,
            'name' => 'メイクセット',
            'brand_name' => '',
            'condition_id' => 2,
            'description' => '便利なメイクアップセット',
            'price' => 2500,
            'item_image' => 'items/MakeUpSet.jpg',
        ]);
        $makeup->categories()->attach([1, 6]);
    }
}
