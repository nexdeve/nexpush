<?php

namespace NexDeve\NexPush\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array send(string $token, string $title, string $body, array $data = [], ?string $imageUrl = null)
 * @method static array sendToUser(int|string $userId, string $title, string $body, array $data = [])
 * @method static array sendMultiple(array $tokens, string $title, string $body, array $data = [])
 * @method static array sendToTopic(string $topic, string $title, string $body, array $data = [])
 * @method static array broadcast(string $title, string $body, array $data = [])
 */
class NexPush extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nexpush';
    }
}
