<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeachingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar permisos según la política de la aplicación
    }

    public function rules(): array
    {
        return [
            'correo' => 'nullable|email|max:255',
            'ei' => 'nullable|string|max:50',
            'ef' => 'nullable|string|max:50',
            'profesion' => 'nullable|string|max:100',
            'nombre' => 'required|string|max:255',
            'area' => 'nullable|string|max:100',
            'adscripcion' => 'nullable|string|max:500',
            'nombre_evento' => 'nullable|string|max:255',
            'tema' => 'nullable|string',
            'fecha' => 'nullable|date',
            'horas' => 'nullable|string|max:50',
            'foja' => 'nullable|string|max:100',
            'modalidad_id' => 'nullable|integer',
            'participacion_id' => 'nullable|integer',
        ];
    }
}
