<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Database\Eloquent\Builder;

class UserController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:App\Models\User|max:255',
            'password' => 'required|string|regex:/\A(?=.*?[a-z])(?=.*?\d)(?=.*?[!-\/:-@[-`{-~])[!-~]{8,100}+\z/i',
        ]);

        $user = User::query()->create([
            'name' => $request->input('name'),
            'password' => Hash::make($request->input('password')),
        ]);

        Auth::login($user);

        return response()->json([
            'status' => 'OK.',
        ], Response::HTTP_OK);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|regex:/\A(?=.*?[a-z])(?=.*?\d)(?=.*?[!-\/:-@[-`{-~])[!-~]{8,100}+\z/i',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return response()->json([
                'status' => 'OK.',
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => 'Error.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
    
        $request->session()->regenerateToken();
    
        return response()->json([
            'status' => 'OK.',
        ], Response::HTTP_OK);
    }

    public function getMe()
    {
        return response()->json([
            'status' => 'OK.',
            'data' => [
                'uuid' => auth()->user()->uuid,
            ],
        ]);
    }

    public function getProfile(string $user_id)
    {
        $user = User::with([
            'messages' => function (Builder $query) {
                $query->orderByDesc('created_at')->limit(100);
            },
            'messages.likes' => function (Builder $query) {
                $query->where('user_uuid', auth()->user()->uuid);
            },
            ])->findOrFail($user_id);

        // message中のuuidをmessage_uuidに変更
        // like_statusを生成
        $messages = $user->messages;
        foreach ($messages as &$message) {
            $message->message_uuid = $message->uuid;
            $message->like_status = count($message->likes) > 0;
            unset($message->likes, $message->uuid);
        }

        return response()->json([
            'status' => 'OK.',
            'data' => [
                'name' => $user->name,
                'uuid' => $user->uuid,
                'profile_message' => $user->profile_message,
                'messages' => $messages,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'string|unique:App\Models\User|max:255',
            'profile' => 'string',
        ]);

        $user = User::query()->where('uuid', auth()->user()->uuid)->firstOrFail();

        if (!is_null($request->input('name'))) {
            $user->name = $request->input('name');
        }
        $user->profile_message = $request->input('profile');
        $user->save();

        return response()->json([
            'status' => 'Changed.',
        ], Response::HTTP_OK);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string|regex:/\A(?=.*?[a-z])(?=.*?\d)(?=.*?[!-\/:-@[-`{-~])[!-~]{8,100}+\z/i',
            'new_password' => 'required|string|regex:/\A(?=.*?[a-z])(?=.*?\d)(?=.*?[!-\/:-@[-`{-~])[!-~]{8,100}+\z/i',
        ]);

        $user = User::query()->where('uuid', auth()->user()->uuid)->firstOrFail();

        if(!Hash::check($request->old_password, $user->password)){
            return response()->json([
                "error" => "Old password doesn't match."
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return response()->json([
            'status' => 'Changed.',
        ], Response::HTTP_OK);
    }
}
