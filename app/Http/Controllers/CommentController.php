<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\SocialAccount;
use App\Services\Social\LinkedIn\LinkedInPublisher;
use App\Services\Social\Twitter\TwitterPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Comment::with('socialAccount')->orderBy('published_at', 'desc');

        if ($request->has('status')) {
            $query->where('reply_status', $request->input('status'));
        }

        $comments = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $comments->items(),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    public function reply(Request $request, string $id, TwitterPublisher $twitter, LinkedInPublisher $linkedIn): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string',
        ]);

        $comment = Comment::with('socialAccount')->findOrFail($id);
        $account = $comment->socialAccount;

        $publisher = $account->provider === 'linkedin' ? $linkedIn : $twitter;
        $replyId = $publisher->postComment($account, $comment->external_post_id, $validated['text']);

        $comment->update([
            'reply_status' => 'sent',
            'reply_external_id' => $replyId,
        ]);

        return response()->json([
            'success' => true,
            'data' => $comment,
        ]);
    }
}
