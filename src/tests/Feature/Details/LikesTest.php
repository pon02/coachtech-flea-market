<?php

namespace Tests\Feature\Details;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Item;
use App\Models\Like;
use Tests\TestCase;

class LikesTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ConditionSeeder::class);
    }

    private function createTestItem()
    {
        $owner = User::factory()->create(['name' => '出品者']);
        $item = Item::factory()->ownedBy($owner)->create([
            'name' => 'テスト商品',
            'price' => 1000,
            'condition_id' => 1,
        ]);
        $item->categories()->attach([1, 2, 3, 4]);
        return $item;
    }

    /** 8. いいね機能 */
    /** 8-1. いいねアイコンを押下することによって、いいねした商品として登録することができる。 */
    public function test_like_item(): void
    {
        $item = $this->createTestItem();
        $likeUser = User::factory()->create(['name' => 'いいねユーザー']);
        $this->actingAs($likeUser);

        $response = $this->get("/item/{$item->id}");
        $response->assertOk()->assertViewIs('detail')->assertViewHas('item');
        $response->assertViewHas('item', fn($viewItem) => $viewItem->likes()->count() === 0);

        $likeResponse = $this->post("/like/{$item->id}");
        $likeResponse->assertOk()->assertJson(['success' => true, 'liked' => true, 'likes_count' => 1]);

        $this->assertDatabaseHas('likes', ['user_id' => $likeUser->id, 'item_id' => $item->id]);

        $afterResponse = $this->get("/item/{$item->id}");
        $afterResponse->assertViewHas('item', fn($viewItem) => $viewItem->likes()->count() === 1);

        $like = Like::where('user_id', $likeUser->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($like);
    }

    /** 8-2. 追加済みのアイコンは色が変化する */
    public function test_like_icon_color_changes(): void
    {
        $item = $this->createTestItem();
        $likeUser = User::factory()->create(['name' => 'いいねユーザー']);
        $this->actingAs($likeUser);

        $response = $this->get("/item/{$item->id}");
        $response->assertOk()->assertViewHas('isLiked', false);
        $htmlContent = $response->getContent();
        $this->assertStringContainsString('like-button', $htmlContent);
        $this->assertStringNotContainsString('like-button liked', $htmlContent);

        $this->post("/like/{$item->id}");

        $afterResponse = $this->get("/item/{$item->id}");
        $afterResponse->assertViewHas('isLiked', true);
        $afterHtmlContent = $afterResponse->getContent();
        $this->assertStringContainsString('like-button liked', $afterHtmlContent);

        // 【技術的根拠】
        // 上記のlikedクラス存在により、以下のCSSセレクタが適用され、確実に色変化が実行される：
        //
        // CSS: .like-button.liked .heart-icon {
        //     filter: invert(20%) sepia(100%) saturate(7000%) hue-rotate(0deg) brightness(100%);
        //     transform: scale(1.1);
        //     animation: heartBeat 0.3s ease;
        // }
        //
        // ↑ このfilterプロパティにより、heart-iconが赤色に変換される
        //   PHPUnitでは実際のピクセル色は検証不可だが、
        //   CSSセレクタの適用条件（likedクラス存在）を確認することで、
        //   色変化の論理的保証を提供している

        $this->assertStringContainsString('heart-icon', $afterHtmlContent);
    }

    /** 8-3. 再度いいねアイコンを押下することによって、いいねを解除することができる */
    public function test_unlike_item(): void
    {
        $item = $this->createTestItem();
        $likeUser = User::factory()->create(['name' => 'いいねユーザー']);
        $this->actingAs($likeUser);

        $this->post("/like/{$item->id}");

        $response = $this->get("/item/{$item->id}");
        $response->assertOk()->assertViewHas('isLiked', true);
        $response->assertViewHas('item', fn($viewItem) => $viewItem->likes()->count() === 1);
        $this->assertStringContainsString('like-button liked', $response->getContent());

        $unlikeResponse = $this->post("/like/{$item->id}");
        $unlikeResponse->assertOk()->assertJson(['success' => true, 'liked' => false, 'likes_count' => 0]);

        $this->assertDatabaseMissing('likes', ['user_id' => $likeUser->id, 'item_id' => $item->id]);

        $afterResponse = $this->get("/item/{$item->id}");
        $afterResponse->assertViewHas('isLiked', false);
        $afterResponse->assertViewHas('item', fn($viewItem) => $viewItem->likes()->count() === 0);
        $afterHtmlContent = $afterResponse->getContent();
        $this->assertStringContainsString('like-button', $afterHtmlContent);
        $this->assertStringNotContainsString('like-button liked', $afterHtmlContent);

        $this->assertNull(Like::where('user_id', $likeUser->id)->where('item_id', $item->id)->first());
    }
}
