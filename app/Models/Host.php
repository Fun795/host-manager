<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Host extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hosts';

    protected $fillable = [
        'hostname',
        'ip',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function options(): BelongsTo
    {
