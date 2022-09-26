<?php

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\MessageController;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{

    public function create(Request $request, $message_uuid)
    {
        $like_doesnt_exist = Like::where([['message_uuid', $message_uuid], ['user_uuid', Auth::id()]])->doesntExist();
        if ($like_doesnt_exist) {
            // like の登録
            $like = new Like;
            $like->fill([
                'message_uuid' => $message_uuid,
                'user_uuid' => Auth::id()
            ]);
            DB::transaction(function () use ($like, $message_uuid) {
                $like->save();
                Message::find($message_uuid)->increment('like_count');
            });
        }
        return response()->json(['status' => Response::HTTP_OK]);
    }

    public function delete(Request $request, $message_uuid)
    {
        $like_exist = Like::where([['message_uuid', $message_uuid], ['user_uuid', Auth::id()]])->exists();
        if ($like_exist) {
            DB::transaction(function () use ($message_uuid) {
                $deleted = Like::where([['message_uuid', $message_uuid], ['user_uuid', Auth::id()]])->delete();
                Message::find($message_uuid)->decrement('like_count');
            });
        }
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
