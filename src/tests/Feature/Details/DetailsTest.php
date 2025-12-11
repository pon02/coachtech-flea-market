<?php

namespace Tests\Feature\Details;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\Item;
use App\Models\User;
use App\Models\Like;
use App\Models\Comment;
use Tests\TestCase;

class DetailsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ConditionSeeder::class);
    }

    private function createItemWithUsers($itemData = [], $categoryIds = [1, 2, 3, 4], $withLikesAndComments = true)
    {
        $owner = User::factory()->create(['name' => '出品者']);

        $defaultItemData = [
            'name' => 'テスト商品',
            'brand_name' => 'テストブランド',
            'price' => 1000,
            'description' => 'これはテスト商品の説明です。',
        ];

        $item = Item::factory()->ownedBy($owner)->create(array_merge($defaultItemData, $itemData));

        if (!empty($categoryIds)) {
            $item->categories()->attach($categoryIds);
        }

        $users = ['owner' => $owner];

        if ($withLikesAndComments) {
            $users['user1'] = User::factory()->create(['name' => 'コメントユーザー1']);
            $users['user2'] = User::factory()->create(['name' => 'コメントユーザー2']);
            $users['likeUser'] = User::factory()->create(['name' => 'いいねユーザー']);

            Like::create(['user_id' => $users['likeUser']->id, 'item_id' => $item->id]);
            Like::create(['user_id' => $users['user1']->id, 'item_id' => $item->id]);
            Like::create(['user_id' => $users['user2']->id, 'item_id' => $item->id]);

            Comment::create([
                'user_id' => $users['user1']->id,
                'item_id' => $item->id,
                'text' => 'これは素晴らしい商品ですね！'
            ]);
            Comment::create([
                'user_id' => $users['user2']->id,
                'item_id' => $item->id,
                'text' => '質問があります。サイズはどれくらいでしょうか？'
            ]);
        }

        return ['item' => $item, 'users' => $users];
    }

    /**7. 商品詳細情報取得 */
    /**7-1. 必要な情報が表示される（商品画像、商品名、ブランド名、価格、いいね数、コメント数、商品説明、商品情報（カテゴリ、商品の状態）、コメント数、コメントしたユーザー情報、コメント内容） */
    public function test_get_item_details(): void
    {
        $testData = $this->createItemWithUsers();
        $item = $testData['item'];
        $users = $testData['users'];

        $response = $this->get("/item/{$item->id}");

        $response->assertOk()
                 ->assertViewIs('detail')
                 ->assertViewHas('item', function ($viewItem) use ($item) {
                     return $viewItem->id === $item->id;
                 });

        $response->assertSee($item->name)
                 ->assertSee($item->brand_name)
                 ->assertSee('¥' . number_format($item->price))
                 ->assertSee($item->description);

        $response->assertSee($item->item_image, false);

        $response->assertSee('これは素晴らしい商品ですね！')
                 ->assertSee('質問があります。サイズはどれくらいでしょうか？')
                 ->assertSee($users['user1']->name)
                 ->assertSee($users['user2']->name);

        $response->assertViewHas('item', function ($viewItem) {
            return $viewItem->likes()->count() === 3 &&
                   $viewItem->comments()->count() === 2 &&
                   $viewItem->categories()->count() === 4 &&
                   $viewItem->categories->contains('name', 'ファッション') &&
                   $viewItem->categories->contains('name', '家電') &&
                   $viewItem->categories->contains('name', 'インテリア') &&
                   $viewItem->categories->contains('name', 'レディース') &&
                   $viewItem->condition !== null;
        });
    }

    /** 7-2. 複数選択されたカテゴリが表示されているか */
    public function test_multiple_categories_are_displayed(): void
    {
        $testData = $this->createItemWithUsers();
        $item = $testData['item'];

        $response = $this->get("/item/{$item->id}");

        $response->assertOk()
                 ->assertViewIs('detail')
                 ->assertViewHas('item');

        $response->assertViewHas('item', function ($viewItem) {
            return $viewItem->categories()->count() === 4 &&
                   $viewItem->categories->contains('name', 'ファッション') &&
                   $viewItem->categories->contains('name', '家電') &&
                   $viewItem->categories->contains('name', 'インテリア') &&
                   $viewItem->categories->contains('name', 'レディース');
        });

        $response->assertSee('ファッション')
                 ->assertSee('家電')
                 ->assertSee('インテリア')
                 ->assertSee('レディース');
    }
}
