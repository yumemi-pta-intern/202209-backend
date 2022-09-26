<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

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

        // user login
        $this->user = new User();
        $this->user->name = 'test user';
        $this->user->hashed_password = Hash::make('password');
        $this->user->save();

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
        $response->assertStatus(200)->assertJson(['status' => Response::HTTP_OK]);

        // message のlike_countが正しく更新されているか
        $message_response = $this->get("/api/message/${message_uuid}");
        $message_response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
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
    }

    public function test_like_delete()
    {
        // message create
        $message = new Message;
        $message->fill([
            'message' => 'test message',
        ]);
        $message->save();

        // like 登録API
        $message_uuid = $message['uuid'];
        $response = $this->post("/api/message/${message_uuid}/like");
        $response->assertStatus(200)->assertJson(['status' => Response::HTTP_OK]);

        // like 削除API
        $message_uuid = $message['uuid'];
        $response = $this->delete("/api/message/${message_uuid}/like");
        $response->assertStatus(200)->assertJson(['status' => Response::HTTP_OK]);

        // message のlike_countが正しく更新されているか
        $message_response = $this->get("/api/message/${message_uuid}");
        $message_response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
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

    public function test_like_create_double()
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
        $response->assertStatus(200)->assertJson(['status' => Response::HTTP_OK]);

        $message_uuid = $message['uuid'];
        $response = $this->post("/api/message/${message_uuid}/like");
        $response->assertStatus(409);
    }

    public function test_like_null_delete()
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
        $response->assertStatus(409);
    }

}