<?php

namespace NexDeve\NexPush\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use NexDeve\NexPush\Models\NexPushToken;

/**
 * NexPushService — Send instant push notifications via FCM
 * Powered by NexDeve | nexdeve.com
 */
class NexPushService
{
    private const FCM_V1_URL = 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send';
    private const FCM_LEGACY_URL = 'https://fcm.googleapis.com/fcm/send';

    private string $serverKey;
    private string $projectId;
    private bool   $useLegacy;

    public function __construct()
    {
        $this->serverKey = config('nexpush.fcm_server_key', '');
        $this->projectId = config('nexpush.fcm_project_id', '');
        $this->useLegacy = config('nexpush.use_legacy_api', true);
    }

    // ── Send to single token ─────────────────────────────────────────────────

    /**
     * Send notification to a single FCM token
     */
    public function send(
        string $token,
        string $title,
        string $body,
        array  $data        = [],
        ?string $imageUrl   = null,
        string  $sound      = 'default',
        string  $priority   = 'high',
        ?string $clickAction = null,
        ?string $channelId  = null,
    ): array {
        $payload = $this->buildPayload(
            token: $token,
            title: $title,
            body: $body,
            data: $data,
            imageUrl: $imageUrl,
            sound: $sound,
            priority: $priority,
            clickAction: $clickAction,
            channelId: $channelId ?? config('nexpush.default_channel_id', 'nexpush_channel'),
        );

        return $this->dispatch($payload);
    }

    // ── Send to user (all devices) ────────────────────────────────────────────

    /**
     * Send notification to ALL devices of a user
     */
    public function sendToUser(
        int|string $userId,
        string     $title,
        string     $body,
        array      $data        = [],
        ?string    $imageUrl    = null,
    ): array {
        $tokens = NexPushToken::where('user_id', $userId)
            ->where('active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => false, 'error' => 'No tokens found for user'];
        }

        return $this->sendMultiple($tokens, $title, $body, $data, $imageUrl);
    }

    // ── Send to multiple tokens ───────────────────────────────────────────────

    /**
     * Send to multiple FCM tokens (batch)
     */
    public function sendMultiple(
        array   $tokens,
        string  $title,
        string  $body,
        array   $data     = [],
        ?string $imageUrl = null,
    ): array {
        $results = ['success' => 0, 'failure' => 0, 'errors' => []];

        // FCM allows max 500 tokens per batch (legacy) or 1 per v1
        $chunks = array_chunk($tokens, 500);

        foreach ($chunks as $chunk) {
            if ($this->useLegacy && count($chunk) > 1) {
                $payload = $this->buildMulticastPayload($chunk, $title, $body, $data, $imageUrl);
                $result  = $this->dispatch($payload);
            } else {
                foreach ($chunk as $token) {
                    $result = $this->send($token, $title, $body, $data, $imageUrl);
                    if ($result['success']) {
                        $results['success']++;
                    } else {
                        $results['failure']++;
                        $results['errors'][] = $result['error'] ?? 'Unknown';
                    }
                }
                continue;
            }

            $results['success'] += $result['success_count'] ?? 0;
            $results['failure'] += $result['failure_count'] ?? 0;
        }

        return $results;
    }

    // ── Send to topic ─────────────────────────────────────────────────────────

    /**
     * Send to a topic (e.g. "news", "offers")
     * All devices subscribed to the topic will receive it
     */
    public function sendToTopic(
        string  $topic,
        string  $title,
        string  $body,
        array   $data     = [],
        ?string $imageUrl = null,
    ): array {
        return $this->send(
            token: '/topics/' . ltrim($topic, '/'),
            title: $title,
            body: $body,
            data: $data,
            imageUrl: $imageUrl,
        );
    }

    // ── Send to all users (broadcast) ─────────────────────────────────────────

    /**
     * Broadcast to ALL registered devices
     */
    public function broadcast(
        string  $title,
        string  $body,
        array   $data     = [],
        ?string $imageUrl = null,
    ): array {
        return $this->sendToTopic('all', $title, $body, $data, $imageUrl);
    }

    // ── Payload Builders ──────────────────────────────────────────────────────

    private function buildPayload(
        string  $token,
        string  $title,
        string  $body,
        array   $data,
        ?string $imageUrl,
        string  $sound,
        string  $priority,
        ?string $clickAction,
        string  $channelId,
    ): array {
        $notification = [
            'title' => $title,
            'body'  => $body,
        ];

        if ($imageUrl) {
            $notification['image'] = $imageUrl;
        }

        return [
            'to' => $token,
            'priority' => $priority,
            'notification' => $notification,
            'data' => array_merge($data, [
                'nexpush' => 'true',
                'click_action' => $clickAction ?? 'FLUTTER_NOTIFICATION_CLICK',
            ]),
            'android' => [
                'priority'              => strtoupper($priority),
                'notification' => [
                    'channel_id'        => $channelId,
                    'sound'             => $sound,
                    'default_sound'     => true,
                    'default_vibrate_timings' => true,
                    'notification_priority'   => 'PRIORITY_MAX',
                    'visibility'        => 'PUBLIC',
                ],
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => ['title' => $title, 'body' => $body],
                        'sound' => $sound,
                        'badge' => 1,
                        'content-available' => 1,
                        'mutable-content'   => 1,
                    ],
                ],
            ],
        ];
    }

    private function buildMulticastPayload(
        array   $tokens,
        string  $title,
        string  $body,
        array   $data,
        ?string $imageUrl,
    ): array {
        $payload = $this->buildPayload(
            token: '', title: $title, body: $body,
            data: $data, imageUrl: $imageUrl,
            sound: 'default', priority: 'high',
            clickAction: null,
            channelId: config('nexpush.default_channel_id', 'nexpush_channel'),
        );

        unset($payload['to']);
        $payload['registration_ids'] = $tokens;

        return $payload;
    }

    // ── HTTP Dispatch ─────────────────────────────────────────────────────────

    private function dispatch(array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type'  => 'application/json',
            ])->post(self::FCM_LEGACY_URL, $payload);

            $body = $response->json();

            if ($response->successful()) {
                Log::channel(config('nexpush.log_channel', 'stack'))
                    ->info('[NexPush] Sent successfully', [
                        'message_id'    => $body['message_id'] ?? null,
                        'success_count' => $body['success'] ?? 1,
                    ]);

                return [
                    'success'       => true,
                    'message_id'    => $body['message_id'] ?? null,
                    'success_count' => $body['success']    ?? 1,
                    'failure_count' => $body['failure']    ?? 0,
                    'response'      => $body,
                ];
            }

            Log::channel(config('nexpush.log_channel', 'stack'))
                ->error('[NexPush] FCM error', ['response' => $body]);

            return [
                'success' => false,
                'error'   => $body['error'] ?? 'FCM request failed',
                'status'  => $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('[NexPush] Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
