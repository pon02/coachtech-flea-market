<?php

namespace Tests\Feature\Mypage;

use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MypageTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ConditionSeeder::class);
        $this->seed(\Database\Seeders\PaymentSeeder::class);
    }

    private function createTestUser($withProfileImage = true)
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'postal_code' => '123-4567',
            'address' => '東京都新宿区西新宿1-1-1',
            'building' => 'ガーデンハウス854',
        ];

        if ($withProfileImage) {
            $userData['profile_image'] = 'profile_images/test_user.jpg';
        }

        return User::factory()->create($userData);
    }

    private function createTestItem($owner, $name = 'テスト商品', $price = 5000)
    {
        $item = Item::factory()->ownedBy($owner)->create([
            'name' => $name,
            'price' => $price,
            'condition_id' => 1,
        ]);
        $item->categories()->attach([1, 2]);
        return $item;
    }

    private function createPurchaseOrder($buyer, $item)
    {
        $payment = Payment::where('name', 'コンビニ払い')->first();
        return Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_id' => $payment->id,
            'price' => $item->price,
            'status' => 'completed',
            'shipping_postal_code' => '123-4567',
            'shipping_address' => '東京都新宿区西新宿1-1-1',
        ]);
    }

    /** 13. ユーザー情報取得 */
    /** 13-1. 必要な情報が取得できる（プロフィール画像、ユーザー名、出品した商品一覧、購入した商品一覧） */
    public function test_mypage_displays_all_required_user_information(): void
    {
        $user = $this->createTestUser(true);
        $seller = User::factory()->create(['name' => '他の出品者']);

        $listedItem1 = $this->createTestItem($user, '出品商品1', 3000);
        $listedItem2 = $this->createTestItem($user, '出品商品2', 7000);

        $purchasedItem1 = $this->createTestItem($seller, '購入商品1', 2000);
        $purchasedItem2 = $this->createTestItem($seller, '購入商品2', 5000);

        $this->createPurchaseOrder($user, $purchasedItem1);
        $this->createPurchaseOrder($user, $purchasedItem2);

        $this->actingAs($user);

        $sellPageResponse = $this->get('/mypage?page=sell');

        $sellPageResponse->assertOk()
                         ->assertViewIs('mypage.mypage')
                         ->assertViewHas('user', $user)
                         ->assertViewHas('listedItems')
                         ->assertViewHas('purchasedItems')
                         ->assertViewHas('page', 'sell')
                         ->assertSee('profile_images/test_user.jpg')
                         ->assertSee('テストユーザー')
                         ->assertSee('出品商品1')
                         ->assertSee('出品商品2');

        $buyPageResponse = $this->get('/mypage?page=buy');

        $buyPageResponse->assertOk()
                        ->assertViewIs('mypage.mypage')
                        ->assertViewHas('user', $user)
                        ->assertViewHas('listedItems')
                        ->assertViewHas('purchasedItems')
                        ->assertViewHas('page', 'buy')
                        ->assertSee('profile_images/test_user.jpg')
                        ->assertSee('テストユーザー')
                        ->assertSee('購入商品1')
                        ->assertSee('購入商品2');

        $sellPageData = $sellPageResponse->viewData('listedItems');
        $purchasedData = $buyPageResponse->viewData('purchasedItems');

        $this->assertCount(2, $sellPageData);

        $this->assertCount(2, $purchasedData);

        $this->assertTrue($sellPageData->contains('name', '出品商品1'));
        $this->assertTrue($sellPageData->contains('name', '出品商品2'));

        $this->assertTrue($purchasedData->contains('name', '購入商品1'));
        $this->assertTrue($purchasedData->contains('name', '購入商品2'));
    }
    /** 14. ユーザー情報変更 */
    /** 14-1. 変更項目が初期値として過去設定されていること（プロフィール画像、ユーザー名、郵便番号、住所） */
    public function test_profile_page_displays_initial_user_values(): void
    {
        $user = $this->createTestUser(true);

        $this->actingAs($user);

        $response = $this->get('/mypage/profile');

        $response->assertOk()
                 ->assertViewIs('mypage.profile');

        $response->assertSee('テストユーザー')
                 ->assertSee('123-4567')
                 ->assertSee('東京都新宿区西新宿1-1-1')
                 ->assertSee('ガーデンハウス854');

        $response->assertSee('profile_images/test_user.jpg');
    }
}