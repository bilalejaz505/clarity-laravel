<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegacyImport extends Model
{
    use HasFactory;

    protected $table = 'legacy_imports';

    protected $guarded = [];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'summary' => 'array',
        ];
    }
}
