<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MessageTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_timeline()
    {   
        $user = new User;
        $user->uuid = '01833eeb-8237-7a5a-897c-58133eb0514d';
        $user->name = 'test user';
        $user->password = 'password';
        Auth::login($user);
        $response = $this->post('/api/message', ['message' => 'test']);
        $response = $this->get('/api/timeline');
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->where('status', Response::HTTP_OK)
                ->has("data", 1, fn ($json) =>
                    $json->where('user_uuid', '01833eeb-8237-7a5a-897c-58133eb0514d')
                    ->where('message', 'test')
                    ->where('like_count', 0)
                    ->where('like_status', 0)
                    ->etc()
            )
        );
        // $response->assertStatus(200)->assertJsonStructure([
        //     'status' => Response::HTTP_OK,
        //     'messages' => [
        //         'user_uuid' => '01833eeb-8237-7a5a-897c-58133eb0514d',
        //         "*"
        //     ]
        // ]);
    }

    public function test_create_login()
    {
        $user = new User;
        $user->uuid = '01833eeb-8237-7a5a-897c-58133eb0514d';
        $user->name = 'test user';
        $user->password = 'password';
        Auth::login($user);
        $response = $this->post('/api/message', ['message' => 'test']);
        $response->assertStatus(200)->assertJson(['status' => Response::HTTP_OK]);
    }
    public function test_create_not_login()
    {
        Auth::shouldReceive('check')->andReturn(false);
        $response = $this->post('/api/message', ['message' => 'test']);
        $response->assertStatus(500);
    
    }

    public function test_show()
    {
        $user = new User;
        $user->uuid = '01833eeb-8237-7a5a-897c-58133eb0514d';
        $user->name = 'test user';
        $user->password = 'password';
        Auth::login($user);
        $response = $this->get('/api/message/');
    }
}
