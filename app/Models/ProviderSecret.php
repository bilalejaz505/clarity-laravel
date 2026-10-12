<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderSecret extends Model
{
    use HasFactory;

    protected $table = 'app_secrets';

    protected $guarded = [];

    protected $hidden = [
        0 => 'ciphertext',
    ];

    protected function casts(): array
    {
        return [
            'ciphertext' => 'encrypted',
        ];
    }
}
