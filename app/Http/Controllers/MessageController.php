<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Message;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = Message::query()
                ->join('users', 'user_uuid', '=', 'users.uuid')
                ->select('messages.uuid', 'name', 'user_uuid', 'message', 'like_count', 'messages.created_at')
                ->withExists('likes as like_status', fn (Builder $query) =>
                    $query->where('user_uuid', Auth::id())
                )->orderByDesc('created_at')->limit(100)->get();
        return response()->json([ 'status' => Response::HTTP_OK, 'data' => $messages ]);
    }

    public function create(Request $request)
    {
        $message = new Message;
        $message->fill([
            'message' => $request->input('message'),
            'user_uuid' => auth()->user()->uuid
        ]);
        $message->save();

        return response()->json([ 'status' => Response::HTTP_OK ]);
    }

    public function show(Request $request, $message_id)
    {
        if (is_null($message_id) || strcmp($message_id, "")  == 0 || Message::query()->whereUuid($message_id)->doesntExist()) {
            return response()->json([
                'status' => 'NG.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $message = Message::query()
                ->join('users', 'user_uuid', '=', 'users.uuid')
                ->select('name', 'user_uuid', 'message', 'like_count', 'messages.created_at')
                ->withExists('likes as like_status', fn (Builder $query) =>
                    $query->where('user_uuid', Auth::id())
                )->where('messages.uuid', $message_id)->orderByDesc('created_at')->get();
        return response()->json(['status' => Response::HTTP_OK, 'data' => $message]);
    }

    public function like(Request $request, $message_uuid)
    {
        DB::transaction(function () use ($message_uuid) {
            $message = Message::find($message_uuid);
            $message->like(Auth::id());
        });
        
        return response()->json(['status' => Response::HTTP_OK]);
    }

    public function delete_like(Request $request, $message_uuid)
    {
        DB::transaction(function () use ($message_uuid) {
            $message = Message::find($message_uuid);
            $message->delete_like(Auth::id());
        });
    
        return response()->json([], Response::HTTP_NO_CONTENT);
    }    
}
