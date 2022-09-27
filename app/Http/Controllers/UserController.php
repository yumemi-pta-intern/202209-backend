<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Database\Eloquent\Builder;
use function PHPUnit\Framework\isNull;

class UserController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:App\Models\User|max:255',
            'password' => 'required|string',
        ]);

        $user = User::query()->create([
            'name' => $request->input('name'),
            'password' => Hash::make($request->input('password')),
        ]);

        Auth::login($user);

        return response('OK.', Response::HTTP_OK);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended('timeline')->with("status", "OK.");
        }

        return back()->withErrors([
            'name' => 'The provided credentials do not match our records.',
        ])->onlyInput('name');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
    
        $request->session()->regenerateToken();
    
        return redirect('/');
    }

    public function getProfile(string $user_id)
    {
        $messages = Message::query()
                            ->join('users', 'user_uuid', '=', 'users.uuid')
                            ->select('messages.uuid', 'name', 'user_uuid', 'message', 'like_count', 'messages.created_at')
                            ->withExists('likes as like_status', fn (Builder $query) =>
                                $query->where('user_uuid', $user_id)
                            )->orderByDesc('created_at')->limit(100)->get();

        return response()->json([
            'status' => 'OK.',
            'data' => [
                'name' => User::query()->firstOrFail($user_id)->name,
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

        if (!isNull($request->input('name'))) {
            $user->name = $request->input('name');
        }
        $user->profile_message = $request->input('profile');
        $user->save();

        return back()->with("status", "Changed.");
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string',
        ]);

        $user = User::query()->where('uuid', auth()->user()->uuid)->firstOrFail();

        if(!Hash::check($request->old_password, $user->password)){
            return back()->with("error", "Old password doesn't match.");
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return back()->with("status", "Changed.");
    }
}
