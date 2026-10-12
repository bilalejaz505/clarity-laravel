<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageReservation extends Model
{
    use HasFactory;

    protected $table = 'usage_reservations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'messages' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
