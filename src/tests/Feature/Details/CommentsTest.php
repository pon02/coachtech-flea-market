<?php

namespace Tests\Feature\Details;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Item;
use App\Models\Comment;
use Tests\TestCase;

class CommentsTest extends TestCase
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
        $itemOwner = User::factory()->create(['name' => '出品者']);
        $item = Item::factory()->ownedBy($itemOwner)->create([
            'name' => 'テスト商品',
            'price' => 1000,
            'condition_id' => 1,
        ]);
        $item->categories()->attach([1, 2, 3, 4]);
        return $item;
    }

    /** 9. コメント送信機能 */
    /** 9-1. ログイン済みのユーザーはコメントを送信できる */
    public function test_logged_in_user_can_submit_comment(): void
    {
        $item = $this->createTestItem();
        $commentUser = User::factory()->create(['name' => 'コメントユーザー']);
        $this->actingAs($commentUser);

        $response = $this->get("/item/{$item->id}");
        $response->assertOk()->assertViewIs('detail')->assertViewHas('item');

        $response->assertViewHas('item', fn($viewItem) => $viewItem->comments()->count() === 0);

        $commentText = 'これはテストコメントです。商品について質問があります。';
        $commentResponse = $this->post("/item/{$item->id}/comment", [
            'text' => $commentText,
        ]);

        $commentResponse->assertRedirect("/item/{$item->id}");

        $this->assertDatabaseHas('comments', [
            'user_id' => $commentUser->id,
            'item_id' => $item->id,
            'text' => $commentText,
        ]);

        $afterResponse = $this->get("/item/{$item->id}");
        $afterResponse->assertOk()->assertViewHas('item');
        $afterResponse->assertViewHas('item', fn($viewItem) => $viewItem->comments()->count() === 1);

        $savedComment = Comment::where('user_id', $commentUser->id)
                              ->where('item_id', $item->id)
                              ->first();

        $this->assertNotNull($savedComment);
        $this->assertEquals($commentText, $savedComment->text);
        $this->assertEquals($commentUser->id, $savedComment->user_id);
        $this->assertEquals($item->id, $savedComment->item_id);
    }

    /** 9-2. ログイン前のユーザーはコメントを送信できない */
    public function test_guest_user_cannot_submit_comment(): void
    {
        $item = $this->createTestItem();

        $response = $this->get("/item/{$item->id}");
        $response->assertOk()->assertViewIs('detail')->assertViewHas('item');

        $response->assertViewHas('item', fn($viewItem) => $viewItem->comments()->count() === 0);

        $commentText = '未ログインユーザーからのコメントです。';
        $commentResponse = $this->post("/item/{$item->id}/comment", [
            'text' => $commentText,
        ]);

        $commentResponse->assertRedirect("/item/{$item->id}");
        $commentResponse->assertSessionHas('error');

        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'text' => $commentText,
        ]);

        $afterResponse = $this->get("/item/{$item->id}");
        $afterResponse->assertOk()->assertViewHas('item');
        $afterResponse->assertViewHas('item', fn($viewItem) => $viewItem->comments()->count() === 0);

        $commentCount = Comment::where('item_id', $item->id)->count();
        $this->assertEquals(0, $commentCount);
    }

    /** 9-3. コメントが入力されていない場合、バリデーションメッセージが表示される */
    public function test_empty_comment_shows_validation_message(): void
    {
        $item = $this->createTestItem();
        $commentUser = User::factory()->create(['name' => 'コメントユーザー']);
        $this->actingAs($commentUser);

        $response = $this->get("/item/{$item->id}");
        $response->assertOk()->assertViewIs('detail')->assertViewHas('item');
        $response->assertViewHas('item', fn($viewItem) => $viewItem->comments()->count() === 0);

        $commentResponse = $this->post("/item/{$item->id}/comment", [
            'text' => '',
        ]);

        $commentResponse->assertRedirect();
        $commentResponse->assertSessionHasErrors(['text']);

        $commentResponse->assertSessionHasErrors([
            'text' => 'コメントを入力してください'
        ]);

        $this->assertDatabaseMissing('comments', [
            'user_id' => $commentUser->id,
            'item_id' => $item->id,
        ]);

        $afterResponse = $this->get("/item/{$item->id}");
        $afterResponse->assertOk()->assertViewHas('item');
        $afterResponse->assertViewHas('item', fn($viewItem) => $viewItem->comments()->count() === 0);

        $commentCount = Comment::where('item_id', $item->id)->count();
        $this->assertEquals(0, $commentCount);
    }

    /** 9-4. コメントが255字以上の場合、バリデーションメッセージが表示される */
    public function test_long_comment_shows_validation_message(): void
    {
        $item = $this->createTestItem();
        $commentUser = User::factory()->create(['name' => 'コメントユーザー']);
        $this->actingAs($commentUser);

        $response = $this->get("/item/{$item->id}");
        $response->assertOk()->assertViewIs('detail')->assertViewHas('item');
        $response->assertViewHas('item', fn($viewItem) => $viewItem->comments()->count() === 0);

        $longComment = str_repeat('あ', 256);
        $commentResponse = $this->post("/item/{$item->id}/comment", [
            'text' => $longComment,
        ]);

        $commentResponse->assertRedirect();
        $commentResponse->assertSessionHasErrors(['text']);

        $commentResponse->assertSessionHasErrors([
            'text' => 'コメントは255文字以内で入力してください'
        ]);

        $this->assertDatabaseMissing('comments', [
            'user_id' => $commentUser->id,
            'item_id' => $item->id,
            'text' => $longComment,
        ]);

        $afterResponse = $this->get("/item/{$item->id}");
        $afterResponse->assertOk()->assertViewHas('item');
        $afterResponse->assertViewHas('item', fn($viewItem) => $viewItem->comments()->count() === 0);

        $commentCount = Comment::where('item_id', $item->id)->count();
        $this->assertEquals(0, $commentCount);
    }
}
