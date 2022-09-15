<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = Message::selectRaw('uuid, message, user_uuid, like_count, EXISTS(SELECT * FROM likes WHERE user_uuid=? && message_uuid=uuid) as like_status, created_at', [Auth::id()])->orderBy('created_at', 'DESC')->get();
        // $messages = DB::table('messages')->get();
        // $like = DB::table("likes")->where('user_uuid', Auth::user()->id)->get();
        return response()->json([ 'status' => Response::HTTP_OK, 'data' => $messages ]);
    }

    public function create(Request $request)
    {
        $message = new Message;
        $message->fill([
            'message' => $request->input('message'),
        ]);
        $message->save();

        return response()->json([ 'status' => Response::HTTP_OK ]);
    }

    public function show(Request $request, $message_id)
    {
        $message = Message::selectRaw('uuid, message, user_uuid, like_count, EXISTS(SELECT * FROM likes WHERE user_uuid=? && message_uuid=uuid) as like_status, created_at', [Auth::id()])->where('uuid', $message_id)->firstOrFail();
        return response()->json(['status' => Response::HTTP_OK, 'data' => $message]);
    }
}
