<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseHistory extends Model
{
    protected $fillable = [
        'user_id',
        'institution',
        'valid_until',
        'uploaded_by',
        'ip_address',
        'filename',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'created_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que subió la licencia
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
