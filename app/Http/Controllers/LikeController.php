<?php

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\MessageController;

class LikeController extends Controller
{

    public function create(Request $request, $message_uuid)
    {
        // like の登録
        $like = new Like;
        $like->fill([
            'message_uuid' => $message_uuid,
            'user_uuid' => Auth::id()
        ]);
        $like->save();

        // like が10より少ない時、messageテーブルのlike_countを更新
        // $int = Like::where('message_uuid', $message_uuid)->count();
        // if ($int < 10) {
        //     $called = app()->make('App\Http\Controllers\MessageController');
        //     $called->updateLikeCount($message_uuid, $int);
        // }
        $called = app()->make('App\Http\Controllers\MessageController');
        $called->countUp($message_uuid);

        return response()->json(['status' => Response::HTTP_OK]);
    }
    public function delete(Request $request, $message_uuid)
    {
        $deleted = Like::where([['message_uuid', $message_uuid], ['user_uuid', Auth::id()]])->delete();

        // $int = Like::where('message_uuid', $message_uuid)->count();
        // if ($int < 10) {
        //     $called = app()->make('App\Http\Controllers\MessageController');
        //     $called->updateLikeCount($message_uuid, $int);
        // }
        $called = app()->make('App\Http\Controllers\MessageController');
        $called->countDown($message_uuid);
        return response()->json(['status' => Response::HTTP_OK]);
    }
}
