<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use UUID\UUID;

class MessageTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    private $user;    
    protected function setUp(): void
    {
        // userの登録
        parent::setUp();
        $this->user = new User;
        $this->user->name = 'test user';
        $this->user->hashed_password = Hash::make('password');
        $this->user->save();

        // 登録したuserでログイン
        Auth::login($this->user);
    }

    // 投稿をし、タイムラインを取ってこれるか
    public function test_timeline()
    {   
        $response = $this->post('/api/message', ['message' => 'test']);
        $response = $this->get('/api/timeline');
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->where('status', Response::HTTP_OK)
                ->has("data", 1, fn ($json) =>
                    $json->where('user_uuid', $this->user->uuid)
                    ->where('message', 'test')
                    ->where('like_count', 0)
                    ->where('like_status', false)
                    ->where('name', $this->user->name)
                    ->etc()
            )
        );
    }

    // いいねをした時、タイムラインに反映されているか
    public function test_timeline_do_like()
    {  
        $message = new Message;
        $message->fill([
            'message' => 'test2'
        ]);
        $message->save();
        $message_uuid = $message['uuid'];

        $like = new Like;
        $like->fill([
            'user_uuid' => Auth::id(),
            'message_uuid' => $message_uuid
        ]);
        $like->save();

        $response = $this->get('/api/timeline');
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->where('status', Response::HTTP_OK)
                ->has("data", 1, fn ($json) =>
                    $json->where('user_uuid', $this->user->uuid)
                    ->where('message', 'test2')
                    ->where('like_count', 0)
                    ->where('like_status', true)
                    ->where('name', $this->user->name)
                    ->etc()
            )
        );
    }

    // ログインしている時に投稿ができるか
    public function test_create_login()
    {
        $response = $this->post('/api/message', ['message' => 'test']);
        $response->assertStatus(200)->assertJson(['status' => Response::HTTP_OK]);
        $message = \App\Models\Message::all()->first();
        $this->assertNotNull($message);
    }

    // ログインしていないときはエラーを返しているか
    public function test_create_not_login()
    {
        Auth::shouldReceive('check')->andReturn(false);
        $response = $this->post('/api/message', ['message' => 'test']);
        $response->assertStatus(401);
    }

    // showメソッドの確認
    public function test_show()
    {
        $message = new Message;
        $message->fill([
            'message' => 'show message test'
        ]);
        $message->save();
        $message_uuid = $message['uuid'];
        $response = $this->get("/api/message/${message_uuid}");
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->where('status', Response::HTTP_OK)
                ->has("data", 1, fn ($json) =>
                    $json->where('user_uuid', $this->user->uuid)
                    ->where('message', 'show message test')
                    ->where('like_count', 0)
                    ->where('like_status', false)
                    ->etc()
            )
        );
    }
}