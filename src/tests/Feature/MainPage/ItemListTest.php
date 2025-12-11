<?php

namespace Tests\Feature\MainPage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Item;
use App\Models\User;
use Tests\TestCase;

class ItemListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** 4. 商品一覧取得機能 */
    /** 4-1. 全商品が表示される */
    public function test_get_all_products(): void
    {
        $items = collect([
            Item::factory()->create(['name' => 'テスト商品1_高性能ノートPC']),
            Item::factory()->create(['name' => 'テスト商品2_ワイヤレスマウス']),
            Item::factory()->create(['name' => 'テスト商品3_USB充電器']),
            Item::factory()->create(['name' => 'テスト商品4_Bluetoothスピーカー']),
            Item::factory()->create(['name' => 'テスト商品5_スマートウォッチ'])
        ]);

        $response = $this->get('/');

        $response->assertOk()
                 ->assertViewIs('main')
                 ->assertViewHas('items');
        $items->each(fn($item) => $response->assertSee($item->name));
    }

    /** 4-2. 購入済み商品は「Sold」と表示される */
    public function test_sold_items_display_sold_label(): void
    {
        $soldItems = collect([
            Item::factory()->sold()->create(['name' => '売却済み商品1_デジタル一眼レフカメラ']),
            Item::factory()->sold()->create(['name' => '売却済み商品2_ゲーミングキーボード']),
            Item::factory()->sold()->create(['name' => '売却済み商品3_プリンター複合機'])
        ]);
        
        $availableItems = collect([
            Item::factory()->create(['name' => '販売中商品1_モニターディスプレイ']),
            Item::factory()->create(['name' => '販売中商品2_外付けハードディスク'])
        ]);

        $response = $this->get('/');

        $response->assertOk();

        $soldCount = substr_count($response->getContent(), 'Sold');
        $this->assertEquals($soldItems->count(), $soldCount);
    }

    /** 4-3. 自分が出品した商品は表示されない */
    public function test_own_items_are_not_displayed(): void
    {
        $currentUser = User::factory()->create([
            'name' => 'ログインユーザー',
            'email' => 'login@example.com'
        ]);

        $ownItems = collect([
            Item::factory()->ownedBy($currentUser)->create(['name' => '自分の出品商品1_ノートパソコン']),
            Item::factory()->ownedBy($currentUser)->create(['name' => '自分の出品商品2_スマートフォン']),
            Item::factory()->ownedBy($currentUser)->create(['name' => '自分の出品商品3_タブレット端末'])
        ]);

        $otherItems = collect([
            Item::factory()->create(['name' => '他人の出品商品1_デジタルカメラ']),
            Item::factory()->create(['name' => '他人の出品商品2_ワイヤレスイヤホン'])
        ]);

        $response = $this->actingAs($currentUser)->get('/');

        $response->assertOk()
                 ->assertViewIs('main')
                 ->assertViewHas('items');

        $responseItems = $response->viewData('items');

        $this->assertEquals($otherItems->count(), $responseItems->count());
        $this->assertTrue($responseItems->pluck('id')->intersect($ownItems->pluck('id'))->isEmpty());

        $otherItems->each(fn($item) => $response->assertSee($item->name));
        $ownItems->each(fn($item) => $response->assertDontSee($item->name));
    }
}
