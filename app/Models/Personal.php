<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Personal extends Model
{
    protected $table = 'personals';

    protected $fillable = [
        'nombre',
        'apellidos',
        'tipo',
        'rfc',
        'numero_checador',
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
            'Título profesional',
            'Constancias de cursos',
            'Cédula profesional'
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

    /**
     * Scope para búsqueda general (nombre, RFC, número de checador)
     */
    public function scopeBusqueda($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'LIKE', "%{$termino}%")
              ->orWhere('apellidos', 'LIKE', "%{$termino}%")
              ->orWhere('rfc', 'LIKE', "%{$termino}%")
              ->orWhere('numero_checador', 'LIKE', "%{$termino}%")
              ->orWhereRaw("CONCAT(nombre, ' ', apellidos) LIKE ?", ["%{$termino}%"]);
        });
    }

    /**
     * Scope para búsqueda por RFC
     */
    public function scopePorRfc($query, $rfc)
    {
        return $query->where('rfc', 'LIKE', "%{$rfc}%");
    }

    /**
     * Scope para búsqueda por número de checador
     */
    public function scopePorNumeroChecador($query, $numero)
    {
        return $query->where('numero_checador', 'LIKE', "%{$numero}%");
    }

    /**
     * Limpiar archivos y carpetas al eliminar personal
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($personal) {
            // La FK constraint se encarga de eliminar documentos automáticamente,
            // pero esto es un respaldo por si acaso
            $carpetaPersonal = 'documentos/personal/' . $personal->id;
            if (Storage::disk('public')->exists($carpetaPersonal)) {
                Storage::disk('public')->deleteDirectory($carpetaPersonal);
            }
        });
    }
}
