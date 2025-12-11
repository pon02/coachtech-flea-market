<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function submitLogin($data = [])
    {
        $defaults = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        return $this->post('/login', array_merge($defaults, $data));
    }

    /** 2.ログイン機能 & 3.ログアウト機能 */
    /** 2-1. メールアドレスが入力されていない場合、バリデーションメッセージが表示される */
    public function test_login_email_validation(): void
    {
        $this->get('/login')->assertOk();

        $this->submitLogin(['email' => ''])
             ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** 2-2. パスワードが入力されていない場合、バリデーションメッセージが表示される */
    public function test_login_password_validation(): void
    {
        $this->get('/login')->assertOk();

        $this->submitLogin(['password' => ''])
             ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** 2-3. 入力情報が間違っている場合、バリデーションメッセージが表示される */
    public function test_login_invalid_credentials(): void
    {
        $this->get('/login')->assertOk();

        $this->submitLogin(['email' => 'invalid@example.com', 'password' => 'wrongpassword'])
             ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    /** 2-4. 正しい情報が入力された場合、ログイン処理が実行される */
    public function test_login_successful(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->submitLogin()
             ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    /** 3-1. ログアウトができる */
    public function test_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post('/logout')
             ->assertRedirect('/');

        $this->assertGuest();
    }
}
