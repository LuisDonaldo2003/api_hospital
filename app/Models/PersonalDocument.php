<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PersonalDocument extends Model
{
    protected $fillable = [
        'personal_id',
        'tipo_documento',
        'nombre_archivo',
        'ruta_archivo',
        'tipo_mime',
        'tamaño_archivo',
        'fecha_subida'
    ];

    protected $casts = [
        'fecha_subida' => 'datetime',
        'tamaño_archivo' => 'integer'
    ];

    /**
     * Relación con el personal
     */
    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    /**
     * Obtener la URL completa del archivo
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->ruta_archivo);
    }

    /**
     * Obtener el tamaño del archivo en formato legible
     */
    public function getTamañoFormateadoAttribute(): string
    {
        $bytes = $this->tamaño_archivo;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Eliminar archivo físico al eliminar el registro
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            if (Storage::exists($document->ruta_archivo)) {
                Storage::delete($document->ruta_archivo);
            }
        });
    }
}
