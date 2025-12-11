<?php

namespace Tests\Feature\Listing;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Condition;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ConditionSeeder::class);
        $this->seed(\Database\Seeders\PaymentSeeder::class);

        Storage::fake('public');
    }

    private function createTestUser()
    {
        return User::factory()->create([
            'name' => '出品者',
            'email' => 'seller@example.com',
        ]);
    }

    /** 15. 出品商品情報登録 */
    /** 15-1. 商品出品画面にて必要な情報が保存できること（カテゴリ、商品の状態、商品名、ブランド名、商品の説明、販売価格） */
    public function test_user_can_create_item_with_all_required_information(): void
    {
        $user = $this->createTestUser();
        $this->actingAs($user);

        $response = $this->get('/sell');
        $response->assertOk()
                 ->assertViewIs('listing')
                 ->assertViewHas('categories')
                 ->assertViewHas('conditions');

        $image = UploadedFile::fake()->create('test-item.jpg', 1024, 'image/jpeg');

        $categories = Category::take(2)->pluck('id')->toArray();
        $condition = Condition::first();

        $itemData = [
            'name' => 'テスト商品名',
            'brand_name' => 'テストブランド',
            'categories' => $categories,
            'condition_id' => $condition->id,
            'description' => 'これはテスト用の商品説明です。商品の詳細情報を記載します。',
            'price' => 15000,
            'item_image' => $image,
        ];

        $response = $this->post('/sell', $itemData);

        $response->assertRedirect('/mypage?page=sell')
                 ->assertSessionHas('success', '商品を出品しました！');

        $this->assertDatabaseHas('items', [
            'user_id' => $user->id,
            'name' => 'テスト商品名',
            'brand_name' => 'テストブランド',
            'condition_id' => $condition->id,
            'description' => 'これはテスト用の商品説明です。商品の詳細情報を記載します。',
            'price' => 15000.00,
        ]);

        $item = Item::where('user_id', $user->id)
                   ->where('name', 'テスト商品名')
                   ->first();

        $this->assertNotNull($item);

        $this->assertNotNull($item->item_image);
        Storage::disk('public')->assertExists($item->item_image);

        $this->assertCount(2, $item->categories);
        foreach ($categories as $categoryId) {
            $this->assertTrue($item->categories->contains('id', $categoryId));
        }

        $this->assertEquals('テスト商品名', $item->name);
        $this->assertEquals('テストブランド', $item->brand_name);
        $this->assertEquals($condition->id, $item->condition_id);
        $this->assertEquals('これはテスト用の商品説明です。商品の詳細情報を記載します。', $item->description);
        $this->assertEquals(15000, $item->price);
        $this->assertEquals($user->id, $item->user_id);
        $this->assertEquals(0, $item->is_sold);
    }
}
