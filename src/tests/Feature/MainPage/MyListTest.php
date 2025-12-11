<?php

namespace Tests\Feature\MainPage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Item;
use App\Models\User;
use App\Models\Like;
use Tests\TestCase;

class MyListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createLike($user, $item)
    {
        return Like::create(['user_id' => $user->id, 'item_id' => $item->id]);
    }

    private function assertMyListResponse($response, $expectedCount = null)
    {
        $response->assertOk()
                 ->assertViewIs('main')
                 ->assertViewHas('items');

        $responseItems = $response->viewData('items');

        if ($expectedCount !== null) {
            $this->assertEquals($expectedCount, $responseItems->count());
        }

        return $responseItems;
    }

    /** 5. マイリスト一覧取得 */
    /** 5-1. いいねした商品だけが表示される */
    public function test_get_liked_items_only(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $likedItems = Item::factory()->count(2)->ownedBy($otherUser)->create();
        $notLikedItem = Item::factory()->ownedBy($otherUser)->create();

        $likedItems->each(fn($item) => $this->createLike($currentUser, $item));

        $response = $this->actingAs($currentUser)->get('/?tab=mylist');
        $responseItems = $this->assertMyListResponse($response, 2);

        $likedItems->each(fn($item) => $response->assertSee($item->name));
        $response->assertDontSee($notLikedItem->name);

        $this->assertTrue($responseItems->pluck('id')->diff($likedItems->pluck('id'))->isEmpty());
        $this->assertFalse($responseItems->contains('id', $notLikedItem->id));
    }

    /** 5-2. 購入済み商品は「Sold」と表示される */
    public function test_sold_label_in_mylist(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $unsoldItem = Item::factory()->ownedBy($otherUser)->create();
        $soldItem = Item::factory()->sold()->ownedBy($otherUser)->create();

        collect([$unsoldItem, $soldItem])->each(fn($item) => $this->createLike($currentUser, $item));

        $response = $this->actingAs($currentUser)->get('/?tab=mylist');
        $responseItems = $this->assertMyListResponse($response, 2);

        $soldItemData = $responseItems->firstWhere('id', $soldItem->id);
        $unsoldItemData = $responseItems->firstWhere('id', $unsoldItem->id);

        $this->assertNotNull($soldItemData, 'Sold商品がレスポンスに含まれていません');
        $this->assertNotNull($unsoldItemData, '未売商品がレスポンスに含まれていません');

        $this->assertTrue((bool)$soldItemData->is_sold, 'Sold商品のis_soldがtrueではありません');
        $this->assertFalse((bool)$unsoldItemData->is_sold, '未売商品のis_soldがfalseではありません');
    }

    /** 5-3. 未認証の場合は何も表示されない */
    public function test_unauthenticated_user_sees_nothing_in_mylist(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();
        $this->createLike($user, $item);

        $response = $this->get('/?tab=mylist');
        $this->assertMyListResponse($response, 0);

        $response->assertDontSee($item->name);
    }
}
