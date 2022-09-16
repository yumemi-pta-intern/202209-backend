<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use App\Models\Like;

class DatabaserelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_messages()
    {
        $count = 10;
        $user = User::factory()
            ->has(Message::factory()->count($count))
            ->create();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('messages', $count);
    }
}
