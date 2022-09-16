<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * アカウント登録
     */
    public function test_making_account()
    {
        $name = 'hogehoge';

        // アカウント登録
        $response = $this->post('/api/signup', [
            'name' => $name,
            'password' => 'hogehoge',
        ]);

        // 200とcookieでセッションIDが返ってきていること
        $response->assertStatus(200);
        $response->assertCookie('laravel_session');
        // $nameの人がDBにあること
        $this->assertDatabaseHas(User::class, ['name'=>$name]);
        // $nameの人がログインしていること
        $user = User::query()->where('name', $name)->first();
        $this->assertAuthenticatedAs($user);
    }

    /**
     * すでに登録されているnameでアカウント登録
     */
    public function test_making_account_with_same_id()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);

        // nameを重複させてアカウント登録
        $response = $this->post('/api/signup', [
            'name' => 'hogehoge',
            'password' => 'hogehoge',
        ]);

        // 400でありユーザーが認証されていないこと
        $response->assertStatus(400);
        $this->assertGuest();
    }

    /**
     * すでに登録されているアカウントでログイン
     */
    public function test_login_with_valid_account()
    {
        $name = 'hogehoge';
        
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);

        // hogehogeでログイン試行
        $response = $this->post('/api/login', [
            'name' => $name,
            'password' => 'hogehoge',
        ]);

        // 200とcookieでセッションIDが返ってきていること
        $response->assertStatus(200);
        $response->assertCookie('laravel_session');
        // $nameの人がログインしていること
        $user = User::query()->where('name', $name)->first();
        $this->assertAuthenticatedAs($user);
    }

    /**
     * すでに登録されているアカウントだが、無効なpwでログイン
     */
    public function test_login_with_invalid_password()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        // DBに誤ったpwでログイン試行
        $response = $this->post('/api/login', [
            'name' => 'hogehoge',
            'password' => 'hugahuga',
        ]);

        // 401でありユーザーが認証されていないこと
        $response->assertStatus(401);
        $this->assertGuest();
    }

    /**
     * 登録されていないアカウントでログイン
     */
    public function test_login_with_invalid_account()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        // DBに登録していないhugahugaユーザーでログイン試行
        $response = $this->post('/api/login', [
            'name' => 'hugahuga',
            'password' => 'hugahuga',
        ]);

        // 401でありユーザーが認証されていないこと
        $response->assertStatus(401);
        $this->assertGuest();
    }

    // 以下はログイン済みであること

    /**
     * ログアウト
     */
    public function test_logout()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        $user = User::query()->where('name', 'hogehoge')->first();

        // hogehogeユーザーでログイン状態に
        $this->actingAs($user);
        // ログアウトを試行
        $response = $this->post('/api/logout');

        // 200でありユーザーが認証されていないこと
        $response->assertStatus(200);
        $this->assertGuest();
    }
    
    /**
     * ユーザー情報を取得
     */
    public function test_getting_user_info()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        $user = User::query()->where('name', 'hogehoge')->first();

        // hogehogeユーザーの情報を取得
        $response = $this->actingAs($user)
                         ->get("/api/user/{$user->uuid}");

        // 200であること
        $response->assertStatus(200);
        // TODO: 正しいname, user_profile, messagesなどが返ってきていること
    }

    /**
     * ユーザー情報を更新
     */
    public function test_update_user_profile()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        $user = User::query()->where('name', 'hogehoge')->first();

        $new_name = 'hugahuga';
        $new_profile = 'hogehogehugahuga';
        // hogehogeユーザーのプロフを更新
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'name' => $new_name,
                            'profile' => $new_profile,
                         ]);

        // 200であること
        $response->assertStatus(200);
        // DBに保存されていること
        $this->assertDatabaseHas(User::class, [
            'name' => $new_name, 'profile' => $new_profile
        ]);
    }

    /**
     * ユーザーのpwを更新
     */
    public function test_update_user_password()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        $user = User::query()->where('name', 'hogehoge')->first();

        $new_password = 'hogehogehugahuga';
        // パスワード更新を試行
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'old_password' => $user->password,
                            'new_password' => $new_password,
                         ]);

        // 200であること
        $response->assertStatus(200);
        // DBに保存されていること
        $this->assertDatabaseHas(User::class, [
            'name' => $user->name, 'hashed_password' => Hash::make($new_password)
        ]);
    }

    /**
     * ユーザーのpwを誤った旧pwで更新
     */
    public function test_update_user_password_with_invalid_password()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        $user = User::query()->where('name', 'hogehoge')->first();

        $new_password = 'hogehogehugahuga';
        // パスワード更新を誤ったパスワードで試行
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'old_password' => 'invalid_pw',
                            'new_password' => $new_password,
                         ]);

        // 401であること
        $response->assertStatus(401);
    }
}
