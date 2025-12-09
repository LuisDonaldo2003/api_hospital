<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LicenseActivation extends Model
{
    protected $fillable = [
        'license_key',
        'hardware_signature',
        'hardware_info',
        'activated_at',
        'deactivated_at',
        'is_active',
        'activation_ip',
        'activated_by',
        'server_info',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'is_active' => 'boolean',
        'hardware_info' => 'array',
        'server_info' => 'array',
    ];

    /**
     * Relación con el usuario que activó
     */
    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Scope para obtener solo activaciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->whereNull('deactivated_at');
    }

    /**
     * Scope para buscar por licencia
     */
    public function scopeByLicense($query, string $licenseKey)
    {
        return $query->where('license_key', $licenseKey);
    }

    /**
     * Scope para buscar por firma de hardware
     */
    public function scopeByHardware($query, string $hardwareSignature)
    {
        return $query->where('hardware_signature', $hardwareSignature);
    }

    /**
     * Desactiva esta activación
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'deactivated_at' => Carbon::now(),
        ]);
    }

    /**
     * Reactiva esta activación
     */
    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'deactivated_at' => null,
            'activated_at' => Carbon::now(),
        ]);
    }

    /**
     * Verifica si esta activación está activa
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active && is_null($this->deactivated_at);
    }
}
