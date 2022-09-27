<?php

namespace Tests\Feature;

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Str;
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
        $name = 'hogehoge';

        // アカウント登録
        $response = $this->postJson('/api/signup', [
            'name' => $name,
            'password' => 'hogehoge',
        ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
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
        $response = $this->postJson('/api/signup', [
            'name' => 'hogehoge',
            'password' => 'hogehoge',
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
        $name = 'hogehoge';
        
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);

        // hogehogeでログイン試行
        $response = $this->postJson('/api/login', [
            'name' => $name,
            'password' => 'hogehoge',
        ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
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
        $this->seed(UserSeeder::class);

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
        $this->seed(UserSeeder::class);
        $user = User::query()->where('name', 'hogehoge')->first();

        // hogehogeユーザーでログイン状態に
        $this->actingAs($user);
        // ログアウトを試行
        $response = $this->postJson('/api/logout');

        // 200が返ってきていて、ユーザーが認証されていないこと
        $response->assertStatus(Response::HTTP_OK);
        $this->assertGuest();
    }
    
    /**
     * ユーザー情報を取得
     */
    // public function test_getting_user_info()
    // {
    //     // DBにhogehogeユーザーを用意
    //     // $user = User::factory()->has(Message::factory()->count(5))->create([
    //     //     'name' => 'hogehoge',
    //     //     'password' => Hash::make('hogehoge'),
    //     // ]);
    //     $user = User::factory()->create([
    //         'name' => 'hogehoge',
    //         'password' => Hash::make('hogehoge'),
    //     ]);
    //     dump($user->uuid);
    //     $message = Message::factory()->make();
    //     $message->user_uuid = $user->uuid;
    //     $message->save();

    //     dump($message);
    //     dd($user);

    //     // hogehogeユーザーの情報を取得
    //     $response = $this->actingAs($user)
    //                      ->get("/api/user/{$user->uuid}");

    //     // 200であること
    //     $response->assertStatus(Response::HTTP_OK);
    //     // TODO: 正しいname, user_profile, messagesなどが返ってきていること
    //     // {
    //     //     "status": 200,
    //     //     "data": [
    //     //         {
    //     //             "name": 名前,
    //     //             "messages": [
    //     //                 {
    //     //                     "user_uuid": 投稿ユーザーのuuid,
    //     //                     "message": 本文,
    //     //                     "like_count": 0,
    //     //                     "like_status": bool,
    //     //                 },
    //     //             ]
    //     //         },
    //     //     ]
    //     // }
    // }

    /**
     * ユーザーの名前を更新
     */
    public function test_update_user_name()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        $user = User::query()->where('name', 'hogehoge')->first();

        // hogehogeユーザーの名前を更新
        $new_name = 'hugahuga';
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'name' => $new_name,
                         ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
        // DBに保存されていること
        $this->assertDatabaseHas(User::class, [
            'name' => $new_name
        ]);
    }

    /**
     * ユーザーのプロフを更新
     */
    public function test_update_user_profile()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        $user = User::query()->where('name', 'hogehoge')->first();

        // hogehogeユーザーのプロフを更新
        $new_profile = 'hogehogehugahuga';
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'profile' => $new_profile,
                         ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
        // DBに保存されていること
        $this->assertDatabaseHas(User::class, [
            'name' => 'hogehoge', 'profile_message' => $new_profile
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

        // パスワード更新を試行
        $new_password = 'hogehogehugahuga';
        $response = $this->actingAs($user)
                         ->put("/api/user/profile", [
                            'old_password' => $user->password,
                            'new_password' => $new_password,
                         ]);

        // 200が返ってきていること
        $response->assertStatus(Response::HTTP_OK);
        // DBに保存されていること
        $user = User::query()->where('name', 'hogehoge')->first();
        dump('in test:' . $new_password);
        $this->assertDatabaseHas(User::class, [
            'name' => $user->name
        ]);
        $this->assertTrue(Hash::check($new_password, $user->password));
    }

    /**
     * ユーザーのpwを誤った旧pwで更新
     */
    public function test_update_user_password_with_invalid_password()
    {
        // DBにhogehogeユーザーを用意
        $this->seed(UserSeeder::class);
        $user = User::query()->where('name', 'hogehoge')->first();

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
