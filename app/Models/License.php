<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class License extends Model
{
    protected $fillable = [
        'institution',
        'license_key',
        'license_data',
        'type',
        'activated_at',
        'expires_at',
        'is_active',
        'features',
        'allowed_domain',
        'signature',
        'activated_by',
        'activation_ip',
        'last_checked_at',
        'hardware_signature',
        'hospital_info',
        'activation_hardware_info',
        'max_activations',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'is_active' => 'boolean',
        'features' => 'array',
        'hospital_info' => 'array',
        'activation_hardware_info' => 'array',
    ];

    /**
     * Relación con el usuario que activó la licencia
     */
    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Verifica si la licencia está activa y válida
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Si es permanente, siempre es válida mientras esté activa
        if ($this->type === 'permanent') {
            return true;
        }

        // Si tiene fecha de expiración, verificar que no haya expirado
        if ($this->expires_at) {
            return Carbon::now()->lte($this->expires_at);
        }

        return false;
    }

    /**
     * Obtiene los días restantes hasta la expiración
     */
    public function daysRemaining(): ?int
    {
        if ($this->type === 'permanent') {
            return null; // Licencia permanente
        }

        if (!$this->expires_at) {
            return null;
        }

        $now = Carbon::now()->startOfDay();
        $expiration = Carbon::parse($this->expires_at)->startOfDay();

        if ($now->gt($expiration)) {
            return 0; // Expirada
        }

        return (int) $now->diffInDays($expiration, false);
    }

    /**
     * Marca la licencia como verificada ahora
     */
    public function markAsChecked(): void
    {
        $this->update(['last_checked_at' => Carbon::now()]);
    }

    /**
     * Desactiva la licencia
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope para obtener solo licencias activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para obtener solo licencias válidas (activas y no expiradas)
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('type', 'permanent')
                  ->orWhere('expires_at', '>', Carbon::now())
                  ->orWhereNull('expires_at');
            });
    }

    /**
     * Verifica si tiene una característica específica habilitada
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->features) {
            return false;
        }

        return in_array($feature, $this->features);
    }

    /**
     * Verifica si el hardware coincide con el autorizado
     */
    public function isValidHardware(string $currentHardwareSignature): bool
    {
        if (!$this->hardware_signature) {
            return true; // Licencias antiguas sin firma de hardware
        }

        return hash_equals($this->hardware_signature, $currentHardwareSignature);
    }

    /**
     * Obtiene la información del hospital
     */
    public function getHospitalInfo(): ?array
    {
        return $this->hospital_info;
    }

    /**
     * Relación con las activaciones
     */
    public function activations()
    {
        return $this->hasMany(LicenseActivation::class, 'license_key', 'license_key');
    }

    /**
     * Obtiene la activación actual activa
     */
    public function currentActivation()
    {
        return $this->activations()->active()->first();
    }
}
