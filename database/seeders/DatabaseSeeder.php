<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use UUID\UUID;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $user = \App\Models\User::create([
            'uuid' => UUID::uuid7(),
            'name' => 'Test User',
            'profile_message' => 'seeder message',
            'password' => Hash::make('password')
        ]);
        \App\Models\Message::create([
            'user_uuid' => $user->uuid,
            'message' => 'test message1'
        ]);
        \App\Models\Message::create([
            'user_uuid' => $user->uuid,
            'message' => 'test message2'
        ]);
    }
}
