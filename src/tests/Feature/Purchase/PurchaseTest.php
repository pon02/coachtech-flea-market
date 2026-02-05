<?php

namespace Tests\Feature\Purchase;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use DatabaseMigrations;

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
        ]);
    }

    private function performPurchase($item, $buyer, $paymentName = 'コンビニ払い')
    {
        $payment = Payment::where('name', $paymentName)->first();
        return $this->post("/purchase/{$item->id}", [
            'payment_id' => $payment->id,
            'postal_code' => $buyer->postal_code,
            'address' => $buyer->address,
        ]);
    }

    /** 10. 購入機能 */
    /** 10-1. 「購入する」ボタンを押下すると購入が完了する */
    public function test_user_can_complete_purchase_with_convenience_store_payment(): void
    {
        $item = $this->createTestItem();
        $buyer = $this->createTestBuyer();
        $this->actingAs($buyer);

        $response = $this->get("/purchase/{$item->id}");
        $response->assertOk()
                 ->assertViewIs('order.order')
                 ->assertViewHas('item')
                 ->assertViewHas('payments')
                 ->assertViewHas('item', fn($viewItem) => $viewItem->id === $item->id);

        $this->assertDatabaseMissing('orders', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        $convenienceStorePayment = Payment::where('name', 'コンビニ払い')->first();
        $purchaseResponse = $this->performPurchase($item, $buyer);

        $purchaseResponse->assertRedirect('/')
                         ->assertSessionHas('success', '商品を購入しました。出品者へのお問い合わせはマイページの取引タブから取引チャットをご利用ください。');

        $order = Order::where('user_id', $buyer->id)
            ->where('item_id', $item->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($order);

        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_id' => $convenienceStorePayment->id,
            'price' => $item->price,
            'status' => 'pending',
            'shipping_postal_code' => $buyer->postal_code,
        ]);

        $this->assertEquals($convenienceStorePayment->id, $order->payment_id);
        $this->assertEquals($item->price, $order->price);
    }

    /** 10-2. 購入した商品は商品一覧画面にて「sold」と表示される */
    public function test_purchased_item_shows_as_sold_on_item_list(): void
    {
        $item = $this->createTestItem();
        $buyer = $this->createTestBuyer();
        $this->actingAs($buyer);

        $this->get('/')
             ->assertOk()
             ->assertViewIs('main')
             ->assertViewHas('items')
             ->assertDontSee('sold');

        $this->get("/purchase/{$item->id}");
        $this->performPurchase($item, $buyer)->assertRedirect('/');

        $this->get('/')
             ->assertOk()
             ->assertViewIs('main')
             ->assertViewHas('items')
             ->assertSee('sold');

        $updatedItem = Item::find($item->id);
        $this->assertTrue($updatedItem->is_sold || $updatedItem->status === 'sold');

        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'status' => 'pending',
        ]);
    }

    /** 10-3. 購入した商品がマイページの購入した商品一覧に追加されている */
    public function test_purchased_item_appears_in_user_profile_purchase_list(): void
    {
        $item = $this->createTestItem();
        $buyer = $this->createTestBuyer();
        $this->actingAs($buyer);

        $this->get('/mypage?page=buy')
             ->assertOk()
             ->assertViewIs('mypage.mypage')
             ->assertViewHas('purchasedItems')
             ->assertViewHas('purchasedItems', fn($purchasedItems) => $purchasedItems->count() === 0);

        $this->get("/purchase/{$item->id}");
        $convenienceStorePayment = Payment::where('name', 'コンビニ払い')->first();
        $this->performPurchase($item, $buyer)->assertRedirect('/');

        $afterResponse = $this->get('/mypage?page=buy');
        $afterResponse->assertOk()
                      ->assertViewIs('mypage.mypage')
                      ->assertViewHas('purchasedItems')
                      ->assertViewHas('purchasedItems', fn($purchasedItems) => $purchasedItems->count() === 1)
                      ->assertViewHas('purchasedItems', function($purchasedItems) use ($item, $buyer) {
                          $purchase = $purchasedItems->first();
                          return $purchase->id === $item->id;
                      })
                      ->assertSee($item->name);

        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_id' => $convenienceStorePayment->id,
            'price' => $item->price,
            'status' => 'pending',
        ]);

        $order = Order::with('item')->where('user_id', $buyer->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals($item->name, $order->item->name);
    }

    /** 11. 支払い方法選択機能 */
    /** 11-1. 購入画面で支払い方法を選択した際に、ページ右カラムの表内支払い方法が選択したものに反映される */
    public function test_payment_method_selection_reflects_in_summary_table(): void
    {
        $item = $this->createTestItem();
        $buyer = $this->createTestBuyer();
        $this->actingAs($buyer);

        $this->get("/purchase/{$item->id}")
             ->assertOk()
             ->assertViewIs('order.order')
             ->assertViewHas(['item', 'payments'])
             ->assertSee('コンビニ払い')
             ->assertSee('カード支払い');

        $convenienceStorePayment = Payment::where('name', 'コンビニ払い')->first();
        $cardPayment = Payment::where('name', 'カード支払い')->first();

        $this->call('GET', "/purchase/{$item->id}", ['payment_method' => $convenienceStorePayment->id])
             ->assertSee('コンビニ払い');

        $this->call('GET', "/purchase/{$item->id}", ['payment_method' => $cardPayment->id])
             ->assertSee('カード支払い');
    }
}
