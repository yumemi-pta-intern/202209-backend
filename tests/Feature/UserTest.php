<?php

namespace Tests\Feature;

use App\Models\Message;
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
            'password' => 'hogehoge1234!',
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
            'password' => Hash::make('hogehoge1234!'),
        ]);

        // nameを重複させてアカウント登録
        $response = $this->postJson('/api/signup', [
            'name' => 'hogehoge',
            'password' => 'hugahuga1234!',
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
            'password' => Hash::make('hogehoge1234!'),
        ]);

        // hogehogeでログイン試行
        $response = $this->postJson('/api/login', [
            'name' => 'hogehoge',
            'password' => 'hogehoge1234!',
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
            'password' => Hash::make('hogehoge1234!'),
        ]);

        // DBに誤ったpwでログイン試行
        $response = $this->postJson('/api/login', [
            'name' => 'hogehoge',
            'password' => 'hugahuga1234!',
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
            'password' => Hash::make('hogehoge1234!'),
        ]);

        // DBに登録していないhugahugaユーザーでログイン試行
        $response = $this->postJson('/api/login', [
            'name' => 'hugahuga',
            'password' => 'hugahuga1234!',
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
            'password' => Hash::make('hogehoge1234!'),
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
     * 自身のuuidを取得
     */
    public function test_get_my_uuid()
    {
        // DBにhogehogeユーザーを用意
        $user = User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge1234!'),
        ]);

        // hogehogeユーザーでログイン状態にして、meにアクセス
        $response = $this->actingAs($user)->getJson('/api/user/me');

        // 200が返ってきていて、uuidが取得できること
        $response->assertStatus(Response::HTTP_OK);
        $response->assertExactJson([
            'status' => 'OK.',
            'data' => [
                'uuid' => $user->uuid,
            ]
        ]);
    }

    /**
     * 自身のプロフィールを取得
     */
    public function test_get_my_profile()
    {
        // DBにhogehogeユーザーを用意
        $user = User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge1234!'),
            'profile_message' => 'my profile',
        ]);

        $message1 = Message::query()->create([
            'user_uuid' => $user->uuid,
            'message' => "1 test message 1."
        ]);
        $message2 = Message::query()->create([
            'user_uuid' => $user->uuid,
            'message' => "2 test message 2."
        ]);

        // hogehogeユーザーでログイン状態にして、自身のプロフィールににアクセス
        $response = $this->actingAs($user)->getJson('/api/user/' . $user->uuid);

        // 200が返ってきていて、uuidが取得できること
        $response->assertStatus(Response::HTTP_OK);
        $response->assertExactJson([
            'status' => 'OK.',
            'data' => [
                'name' => $user->name,
                'uuid' => $user->uuid,
                'profile_message' => $user->profile_message,
                'messages' => [
                    [
                        'message_uuid' => $message1->uuid,
                        'user_uuid' => $message1->user_uuid,
                        'message' => $message1->message,
                        'like_count' => $message1->like_count,
                        'like_status' => false,
                        'created_at' => $message1->created_at,
                    ],
                    [
                        'message_uuid' => $message2->uuid,
                        'user_uuid' => $message2->user_uuid,
                        'message' => $message2->message,
                        'like_count' => $message2->like_count,
                        'like_status' => false,
                        'created_at' => $message2->created_at,
                    ],
                ],
            ]
        ]);
    }

    /**
     * ユーザーの名前を更新
     */
    public function test_update_user_name()
    {
        // DBにhogehogeユーザーを用意
        $user = User::query()->create([
            'name' => 'hogehoge',
            'password' => Hash::make('hogehoge1234!'),
        ]);

        // hogehogeユーザーの名前を更新
        $new_name = 'hugahuga';
        $response = $this->actingAs($user)
                         ->putJson("/api/user/profile", [
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
            'password' => Hash::make('hogehoge1234!'),
        ]);

        // hogehogeユーザーのプロフを更新
        $new_profile = 'hogehogehugahuga';
        $response = $this->actingAs($user)
                         ->putJson("/api/user/profile", [
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
            'password' => Hash::make('hogehoge1234!'),
        ]);

        // パスワード更新を試行
        $new_password = 'hogehogehugahuga';
        $response = $this->actingAs($user)
                         ->putJson("/api/user/password", [
                            'old_password' => 'hogehoge1234!',
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
            'password' => Hash::make('hogehoge1234!'),
        ]);

        // パスワード更新を誤ったパスワードで試行
        $new_password = 'hogehogehugahuga';
        $response = $this->actingAs($user)
                         ->putJson("/api/user/password", [
                            'old_password' => 'invalid_pw1234!',
                            'new_password' => $new_password,
                         ]);

        // 422であること
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        // DBに保存されているパスワードが別であること
        $user->refresh();
        $this->assertFalse(Hash::check($new_password, $user->password));
    }
}
