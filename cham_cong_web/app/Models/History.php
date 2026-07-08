<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class History extends Model
{
    protected $table = 'history';

    protected $fillable = [
        'user_id',
        'rfid_uid',
        'check_in',
        'check_out',
    ];

    protected function casts(): array
    {
        return [
            'check_in'  => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
