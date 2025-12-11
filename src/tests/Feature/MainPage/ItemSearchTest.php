<?php

namespace Tests\Feature\MainPage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Item;
use App\Models\User;
use Tests\TestCase;

class ItemSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function assertSearchResponse($response, $expectedCount = null)
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

    /** 6. 商品検索機能 */
    /** 6-1. 「商品名」で部分一致検索ができる */
    public function test_search_items_by_name(): void
    {
        $matchingItem = Item::factory()->create(['name' => '特別な腕時計']);
        $partialMatchItem = Item::factory()->create(['name' => '特別限定品']);
        $nonMatchingItem = Item::factory()->create(['name' => '一般的な商品']);

        $response = $this->get('/?keyword=特別');
        $responseItems = $this->assertSearchResponse($response, 2);

        $this->assertTrue($responseItems->contains('id', $matchingItem->id));
        $this->assertTrue($responseItems->contains('id', $partialMatchItem->id));
        $this->assertFalse($responseItems->contains('id', $nonMatchingItem->id));

        $response->assertSee($matchingItem->name)
                 ->assertSee($partialMatchItem->name)
                 ->assertDontSee($nonMatchingItem->name);
    }

    /** 6-2. 検索状態がマイリストでも保持されている */
    public function test_search_state_is_retained_in_mylist(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $matchingLikedItem = Item::factory()->ownedBy($otherUser)->create(['name' => '特別な腕時計']);
        $nonMatchingLikedItem = Item::factory()->ownedBy($otherUser)->create(['name' => '一般的な商品']);
        $matchingNotLikedItem = Item::factory()->ownedBy($otherUser)->create(['name' => '特別限定品']);

        \App\Models\Like::create(['user_id' => $user->id, 'item_id' => $matchingLikedItem->id]);
        \App\Models\Like::create(['user_id' => $user->id, 'item_id' => $nonMatchingLikedItem->id]);

        $mainResponse = $this->actingAs($user)->get('/?keyword=特別');
        $mainResponse->assertOk();

        $response = $this->actingAs($user)->get('/?tab=mylist&keyword=特別');
        $responseItems = $this->assertSearchResponse($response, 1);

        $this->assertTrue($responseItems->contains('id', $matchingLikedItem->id), 'いいねした検索一致商品が表示されていません');

        $this->assertFalse($responseItems->contains('id', $nonMatchingLikedItem->id), 'いいねしたが検索条件に一致しない商品が表示されています');

        $this->assertFalse($responseItems->contains('id', $matchingNotLikedItem->id), 'いいねしていない商品が表示されています');

        $response->assertSee('value="特別"', false);

        $response->assertSee($matchingLikedItem->name)
                 ->assertDontSee($nonMatchingLikedItem->name)
                 ->assertDontSee($matchingNotLikedItem->name);
    }
}
