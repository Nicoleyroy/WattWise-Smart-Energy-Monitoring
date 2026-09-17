<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class DatabaseNotification extends Model
{
    protected $collection = 'notifications';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    public function markAsUnread(): void
    {
        if ($this->read_at !== null) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    public function read(): bool
    {
        return $this->read_at !== null;
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
