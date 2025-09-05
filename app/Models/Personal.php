<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personal extends Model
{
    protected $table = 'personals';

    protected $fillable = [
        'nombre',
        'apellidos',
        'tipo',
        'fecha_ingreso',
        'activo'
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'activo' => 'boolean'
    ];

    /**
     * Relación con documentos del personal
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(PersonalDocument::class);
    }

    /**
     * Verificar si todos los documentos están completos
     */
    public function getDocumentosCompletosAttribute(): bool
    {
        $tiposRequeridos = [
            'Acta de nacimiento',
            'Comprobante de domicilio',
            'CURP',
            'INE',
            'RFC',
            'Título profesional'
        ];

        $documentosSubidos = $this->documentos()->pluck('tipo_documento')->toArray();
        
        return count(array_intersect($tiposRequeridos, $documentosSubidos)) === count($tiposRequeridos);
    }

    /**
     * Scope para filtrar por tipo de personal
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para personal activo
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}
