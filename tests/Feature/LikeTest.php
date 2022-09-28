<?php

namespace Tests\Feature;

use App\Http\Controllers\LikeController;
use App\Models\Like;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use UUID\UUID;

class LikeTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    private $user;
    public function setUp(): void
    {
        parent::setUp();

        // user 作成
        $this->user = new User();
        $this->user->name = 'test user';
        $this->user->password = Hash::make('password');
        $this->user->save();

        // user2 作成
        $this->user2 = new User();
        $this->user2->name = 'test user2';
        $this->user2->password = Hash::make('password');
        $this->user2->save();

        Auth::login($this->user);
    }

    public function test_like_create()
    {
        // message 作成
        $message = new Message;
        $message->fill([
            'message' => 'test message',
        ]);
        $message->save();

        // like 登録API
        $message_uuid = $message['uuid'];
        $response = $this->post("/api/message/${message_uuid}/like");
        $response->assertStatus(Response::HTTP_OK)->assertJson(['status' => Response::HTTP_OK]);

        // message のlike_countが正しく更新されているか
        $message_response = $this->get("/api/message/${message_uuid}");
        $message_response->assertStatus(Response::HTTP_OK)->assertJson(fn (AssertableJson $json) =>
            $json->where('status', Response::HTTP_OK)
            ->has("data", 1, fn ($json) =>
                $json->where('user_uuid', $this->user->uuid)
                ->where('message', 'test message')
                ->where('like_count', 1)
                ->where('like_status', true)
                ->where('name', $this->user->name)
                ->etc()
            )
        );

        // dbの確認
        $this->assertDatabaseHas('likes', ['user_uuid' => Auth::id(), 'message_uuid' => $message_uuid]);
    }

    public function test_like_delete()
    {
        // message 作成
        $message = new Message;
        $message->fill([
            'message' => 'test message',
        ]);
        $message->save();

        // like 登録API
        $message_uuid = $message['uuid'];
        $response = $this->post("/api/message/${message_uuid}/like");
        $response->assertStatus(Response::HTTP_OK)->assertJson(['status' => Response::HTTP_OK]);
        $this->assertDatabaseHas('likes', ['user_uuid' => Auth::id(), 'message_uuid' => $message_uuid]);

        // like 削除API
        $message_uuid = $message['uuid'];
        $response = $this->delete("/api/message/${message_uuid}/like");
        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('likes', ['user_uuid' => Auth::id(), 'message_uuid' => $message_uuid]);

        // message のlike_count の確認
        $message_response = $this->get("/api/message/${message_uuid}");
        $message_response->assertStatus(Response::HTTP_OK)->assertJson(fn (AssertableJson $json) =>
            $json->where('status', Response::HTTP_OK)
            ->has("data", 1, fn ($json) =>
                $json->where('user_uuid', $this->user->uuid)
                ->where('message', 'test message')
                ->where('like_count', 0)
                ->where('like_status', false)
                ->where('name', $this->user->name)
                ->etc()
            )
        );
    }

    public function test_like_create_duplicate()
    {
        // message 作成
        $message = new Message;
        $message->fill([
            'message' => 'test message',
        ]);
        $message->save();

        // like 登録API
        $message_uuid = $message['uuid'];
        $response = $this->post("/api/message/${message_uuid}/like");
        $response->assertStatus(Response::HTTP_OK);

        $message_uuid = $message['uuid'];
        $response = $this->post("/api/message/${message_uuid}/like");
        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('likes', ['user_uuid' => Auth::id(), 'message_uuid' => $message_uuid]);
    }

    public function test_like_delete_null()
    {
        // message 作成
        $message = new Message;
        $message->fill([
            'message' => 'test message',
        ]);
        $message->save();

        // like 削除API
        $message_uuid = $message['uuid'];
        $response = $this->delete("/api/message/${message_uuid}/like");
        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('likes', ['user_uuid' => Auth::id(), 'message_uuid' => $message_uuid]);
    }

    public function test_like_create_two_user() 
    {
        // message 作成
        $message = new Message;
        $message->fill([
            'message' => 'test message',
        ]);
        $message->save();

        // like 登録API
        $message_uuid = $message['uuid'];
        $response = $this->post("/api/message/${message_uuid}/like");
        $response->assertStatus(Response::HTTP_OK)->assertJson(['status' => Response::HTTP_OK]);
        $this->assertDatabaseHas('likes', ['user_uuid' => Auth::id(), 'message_uuid' => $message_uuid]);

        // user 変更
        Auth::login($this->user2);

        // like 登録API
        $message_uuid = $message['uuid'];
        $response = $this->post("/api/message/${message_uuid}/like");
        $response->assertStatus(Response::HTTP_OK)->assertJson(['status' => Response::HTTP_OK]);
        $this->assertDatabaseHas('likes', ['user_uuid' => Auth::id(), 'message_uuid' => $message_uuid]);

        // message のlike_count の確認
        $message_response = $this->get("/api/message/${message_uuid}");
        $message_response->assertStatus(Response::HTTP_OK)->assertJson(fn (AssertableJson $json) =>
            $json->where('status', Response::HTTP_OK)
            ->has("data", 1, fn ($json) =>
                $json->where('user_uuid', $this->user->uuid)
                ->where('message', 'test message')
                ->where('like_count', 2)
                ->where('like_status', true)
                ->where('name', $this->user->name)
                ->etc()
            )
        );
    }

}