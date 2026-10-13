<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingEvent extends Model
{
    use HasFactory;

    protected $table = 'billing_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [];
    }
}
