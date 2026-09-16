<?php

namespace NexDeve\NexPush\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use NexDeve\NexPush\Models\NexPushToken;
use NexDeve\NexPush\Services\NexPushService;

class NexPushController extends Controller
{
    public function __construct(private NexPushService $push) {}

    /**
     * POST /api/nexpush/register
     * Register FCM token from device
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string|max:512',
            'platform' => 'required|in:android,ios,web',
            'user_id'  => 'nullable',
        ]);

        NexPushToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id'     => $request->user_id ?? auth()->id(),
                'platform'    => $request->platform,
                'app_version' => $request->app_version,
                'active'      => true,
                'last_seen'   => now(),
            ]
        );

        // Auto-subscribe to 'all' topic
        $this->push->sendToTopic('all', '', ''); // triggers subscribe on FCM side

        return response()->json(['message' => 'Token registered', 'status' => 'ok']);
    }

    /**
     * POST /api/nexpush/send
     * Send notification (server-to-server or app-to-server)
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'to'    => 'required|string',
            'title' => 'required|string|max:200',
            'body'  => 'required|string|max:1000',
            'data'  => 'nullable|array',
        ]);

        $result = $this->push->send(
            token:    $request->to,
            title:    $request->title,
            body:     $request->body,
            data:     $request->data ?? [],
            imageUrl: $request->image_url,
            priority: $request->priority ?? 'high',
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * POST /api/nexpush/send-to-user/{userId}
     */
    public function sendToUser(Request $request, $userId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'body'  => 'required|string',
            'data'  => 'nullable|array',
        ]);

        $result = $this->push->sendToUser(
            userId:   $userId,
            title:    $request->title,
            body:     $request->body,
            data:     $request->data ?? [],
            imageUrl: $request->image_url,
        );

        return response()->json($result);
    }

    /**
     * POST /api/nexpush/broadcast
     */
    public function broadcast(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'body'  => 'required|string',
        ]);

        $result = $this->push->broadcast(
            title:    $request->title,
            body:     $request->body,
            data:     $request->data ?? [],
            imageUrl: $request->image_url,
        );

        return response()->json($result);
    }

    /**
     * DELETE /api/nexpush/unregister
     */
    public function unregister(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        NexPushToken::where('token', $request->token)
            ->update(['active' => false]);

        return response()->json(['message' => 'Token unregistered']);
    }
}
