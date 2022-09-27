<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * アカウント登録
     */
    public function test_making_account()
    {
        // アカウント登録
        $response = $this->postJson('/api/signup', [
            'name' => 'hogehoge',
            'password' => 'hogehoge',
        ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
        // $nameの人がDBにあること
        $this->assertDatabaseHas(User::class, ['name'=>'hogehoge']);
        // $nameの人がログインしていること
        $user = User::query()->where('name', 'hogehoge')->first();
        $this->assertAuthenticatedAs($user);
    }

    /**
     * すでに登録されているnameでアカウント登録
     */
    public function test_making_account_with_same_id()
    {
        // DBにhogehogeユーザーを用意
        User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge'),
        ]);

        // nameを重複させてアカウント登録
        $response = $this->postJson('/api/signup', [
            'name' => 'hogehoge',
            'password' => 'hugahuga',
        ]);

        // 422でありユーザーが認証されていないこと
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertGuest();
    }

    /**
     * すでに登録されているアカウントでログイン
     */
    public function test_login_with_valid_account()
    {
       
        // DBにhogehogeユーザーを用意
        User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge'),
        ]);

        // hogehogeでログイン試行
        $response = $this->postJson('/api/login', [
            'name' => 'hogehoge',
            'password' => 'hogehoge',
        ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
        // $nameの人がログインしていること
        $user = User::query()->where('name', 'hogehoge')->first();
        $this->assertAuthenticatedAs($user);
    }

    /**
     * すでに登録されているアカウントだが、無効なpwでログイン
     */
    public function test_login_with_invalid_password()
    {
        // DBにhogehogeユーザーを用意
        User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge'),
        ]);

        // DBに誤ったpwでログイン試行
        $response = $this->postJson('/api/login', [
            'name' => 'hogehoge',
            'password' => 'hugahuga',
        ]);

        // 422でありユーザーが認証されていないこと
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertGuest();
    }

    /**
     * 登録されていないアカウントでログイン
     */
    public function test_login_with_invalid_account()
    {
        // DBにhogehogeユーザーを用意
        User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge'),
        ]);

        // DBに登録していないhugahugaユーザーでログイン試行
        $response = $this->postJson('/api/login', [
            'name' => 'hugahuga',
            'password' => 'hugahuga',
        ]);

        // 422でありユーザーが認証されていないこと
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertGuest();
    }

    // 以下はログイン済みであること

    /**
     * ログアウト
     */
    public function test_logout()
    {
        // DBにhogehogeユーザーを用意
        $user = User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge'),
        ]);

        // hogehogeユーザーでログイン状態に
        $this->actingAs($user);
        // ログアウトを試行
        $response = $this->postJson('/api/logout');

        // 200が返ってきていて、ユーザーが認証されていないこと
        $response->assertStatus(Response::HTTP_OK);
        $this->assertGuest();
    }

    /**
     * ユーザーの名前を更新
     */
    public function test_update_user_name()
    {
        // DBにhogehogeユーザーを用意
        $user = User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge'),
        ]);

        // hogehogeユーザーの名前を更新
        $new_name = 'hugahuga';
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'name' => $new_name,
                         ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
        // DBに保存されていること
        $user->refresh();
        $this->assertEquals($new_name, $user->name);
    }

    /**
     * ユーザーのプロフを更新
     */
    public function test_update_user_profile()
    {
        // DBにhogehogeユーザーを用意
        $user = User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge'),
        ]);

        // hogehogeユーザーのプロフを更新
        $new_profile = 'hogehogehugahuga';
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'profile' => $new_profile,
                         ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
        // DBに保存されていること
        $user->refresh();
        $this->assertEquals($new_profile, $user->profile_message);
    }

    /**
     * ユーザーのpwを更新
     */
    public function test_update_user_password()
    {
        // DBにhogehogeユーザーを用意
        $user = User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge'),
        ]);

        // パスワード更新を試行
        $new_password = 'hogehogehugahuga';
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'old_password' => 'hogehoge',
                            'new_password' => $new_password,
                         ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
        // DBに保存されていること
        $user->refresh();
        $this->assertTrue(Hash::check($new_password, $user->password));
    }

    /**
     * ユーザーのpwを誤った旧pwで更新
     */
    public function test_update_user_password_with_invalid_password()
    {
        // DBにhogehogeユーザーを用意
        $user = User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge'),
        ]);

        // パスワード更新を誤ったパスワードで試行
        $new_password = 'hogehogehugahuga';
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'old_password' => 'invalid_pw',
                            'new_password' => $new_password,
                         ]);

        // 422であること
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
