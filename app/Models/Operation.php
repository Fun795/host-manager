<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Operation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'operations';

    protected $fillable = [
        'type',
        'status',
        'host_id',
        'payload',
        'idempotency_key',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class, 'host_id', 'id');
    }
}
