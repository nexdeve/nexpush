<?php

namespace NexDeve\NexPush\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NexPushToken extends Model
{
    use SoftDeletes;

    protected $table = 'nexpush_tokens';

    protected $fillable = [
        'user_id', 'token', 'platform',
        'app_version', 'active', 'last_seen',
    ];

    protected $casts = [
        'active'    => 'boolean',
        'last_seen' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
