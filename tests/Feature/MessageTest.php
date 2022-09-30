<?php

namespace Tests\Feature;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

use App\Models\Like;
use App\Models\Message;
use App\Models\User;

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
        $this->user->password = Hash::make('password');
        $this->user->save();
    }

    // 投稿をし、タイムラインを取ってこれるか
    public function test_timeline()
    {   
        $response = $this->actingAs($this->user)->post('/api/message', ['message' => 'test']);
        $response = $this->get('/api/timeline');
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->where('message', 'OK.')
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
            'message' => 'test2',
            'user_uuid' => $this->user->uuid
        ]);
        $message->save();
        
        $message_uuid = $message['uuid'];

        $like = new Like;
        $like->fill([
            'user_uuid' => $this->user->uuid,
            'message_uuid' => $message_uuid
        ]);
        $like->save();
        $message->increment('like_count');

        $response = $this->actingAs($this->user)->get('/api/timeline');
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->where('message', 'OK.')
                ->has("data", 1, fn ($json) =>
                    $json->where('user_uuid', $this->user->uuid)
                    ->where('message', 'test2')
                    ->where('like_count', 1)
                    ->where('like_status', true)
                    ->where('name', $this->user->name)
                    ->etc()
            )
        );
    }

    // ログインしている時に投稿ができるか
    public function test_create_login()
    {
        $response = $this->actingAs($this->user)->postJson('/api/message', ['message' => 'test']);
        $response->assertStatus(200)->assertJson(['message' => 'OK.']);
        $message = \App\Models\Message::all()->first();
        $this->assertNotNull($message);
    }

    // ログインしていないときはエラーを返しているか
    public function test_create_not_login()
    {
        $response = $this->postJson('/api/message', ['message' => 'test']);
        $response->assertStatus(401);
    }

    // showメソッドの確認
    public function test_show()
    {
        $message = new Message;
        $message->fill([
            'message' => 'show message test',
            'user_uuid' => $this->user->uuid
        ]);
        $message->save();
        $message_uuid = $message['uuid'];
        $response = $this->actingAs($this->user)->get("/api/message/${message_uuid}");
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->where('message', 'OK.')
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
