<?php

namespace Tests\Feature\Purchase;

use App\Models\User;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ShippingAddressTest extends TestCase
{
    use DatabaseMigrations;

    private const NEW_POSTAL_CODE = '987-6543';
    private const NEW_ADDRESS = '大阪府大阪市北区梅田2-2-2';
    private const NEW_BUILDING = '梅田マンション101';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ConditionSeeder::class);
        $this->seed(\Database\Seeders\PaymentSeeder::class);
    }

    private function createTestItem()
    {
        $seller = User::factory()->create(['name' => '出品者']);
        $item = Item::factory()->ownedBy($seller)->create([
            'name' => 'テスト商品',
            'price' => 5000,
            'condition_id' => 1,
        ]);
        $item->categories()->attach([1, 2, 3, 4]);
        return $item;
    }

    private function createTestBuyer()
    {
        return User::factory()->create([
            'name' => '購入者',
            'postal_code' => '123-4567',
            'address' => '東京都新宿区西新宿1-1-1',
            'building' => 'ガーデンハウス854',
        ]);
    }

    /** 12. 配送先変更機能 */
    /** 12-1. 送付先住所変更画面にて登録した住所が商品購入画面に反映されている */
    public function test_shipping_address_reflects_on_purchase_page(): void
    {
        $item = $this->createTestItem();
        $buyer = $this->createTestBuyer();
        $this->actingAs($buyer);

        $addressEditResponse = $this->get("/purchase/address/{$item->id}");
        $addressEditResponse->assertOk()
                           ->assertViewIs('order.shippingAddress')
                           ->assertViewHas('item')
                           ->assertViewHas('shippingData');

        $this->post("/purchase/address/{$item->id}", [
            'postal_code' => self::NEW_POSTAL_CODE,
            'address' => self::NEW_ADDRESS,
            'building' => self::NEW_BUILDING,
        ])->assertRedirect("/purchase/{$item->id}")
          ->assertSessionHas('success', '配送先住所を変更しました。');

        $response = $this->get("/purchase/{$item->id}");

        $response->assertOk()
                 ->assertViewIs('order.order')
                 ->assertViewHas('shippingData', function($shippingData) {
                     return $shippingData['postal_code'] === self::NEW_POSTAL_CODE &&
                            $shippingData['address'] === self::NEW_ADDRESS &&
                            $shippingData['building'] === self::NEW_BUILDING;
                 })
                 ->assertSee(self::NEW_POSTAL_CODE)
                 ->assertSee(self::NEW_ADDRESS)
                 ->assertSee(self::NEW_BUILDING);
    }

    /** 12-2. 購入した商品に送付先住所が紐づいて登録される */
    public function test_purchased_item_linked_with_shipping_address(): void
    {
        $item = $this->createTestItem();
        $buyer = $this->createTestBuyer();
        $this->actingAs($buyer);

        $this->post("/purchase/address/{$item->id}", [
            'postal_code' => self::NEW_POSTAL_CODE,
            'address' => self::NEW_ADDRESS,
            'building' => self::NEW_BUILDING,
        ])->assertRedirect("/purchase/{$item->id}")
          ->assertSessionHas('success', '配送先住所を変更しました。');

        $convenienceStorePayment = Payment::where('name', 'コンビニ払い')->first();
        $purchaseResponse = $this->post("/purchase/{$item->id}", [
            'payment_id' => $convenienceStorePayment->id,
            'postal_code' => self::NEW_POSTAL_CODE,
            'address' => self::NEW_ADDRESS,
        ]);

        $purchaseResponse->assertRedirect('/');

        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_id' => $convenienceStorePayment->id,
            'price' => $item->price,
            'status' => 'pending',
            'shipping_postal_code' => self::NEW_POSTAL_CODE,
        ]);

        $order = Order::where('user_id', $buyer->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($order);
        $this->assertStringContainsString(self::NEW_ADDRESS, $order->shipping_address);
        $this->assertStringContainsString(self::NEW_BUILDING, $order->shipping_address);
    }
}
