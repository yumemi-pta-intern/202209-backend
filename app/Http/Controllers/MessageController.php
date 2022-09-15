<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Message;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = DB::table('messages')->get();

        return response()->json($messages);
    }

    public function create(Request $request)
    {
        $message = new Message;
        $message->create([
            'message' => 'test message',
        ]);
    }

    public function show(Request $request)
    {
        $message = DB::table('messages')->where('uuid', $request->input('uuid'));
        return response()->json($message);
    }
}
