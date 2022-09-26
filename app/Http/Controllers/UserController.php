<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

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

        return response('OK', Response::HTTP_OK);
    }
}
