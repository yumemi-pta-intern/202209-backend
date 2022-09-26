<?php

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\MessageController;
use App\Models\Message;

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
            $like->save();
            Message::find($message_uuid)->increment('like_count');
            return response()->json(['status' => Response::HTTP_OK]);
        } else {
            return response()->json(['status' => Response::HTTP_CONFLICT], Response::HTTP_CONFLICT);
        }
    }
    public function delete(Request $request, $message_uuid)
    {
        $like_exist = Like::where([['message_uuid', $message_uuid], ['user_uuid', Auth::id()]])->exists();
        if ($like_exist) {
            $deleted = Like::where([['message_uuid', $message_uuid], ['user_uuid', Auth::id()]])->delete();
            Message::find($message_uuid)->decrement('like_count');
            return response()->json(['status' => Response::HTTP_OK]);
        } else {
            return response()->json(['status' => Response::HTTP_CONFLICT], Response::HTTP_CONFLICT);
        }
    }
}
