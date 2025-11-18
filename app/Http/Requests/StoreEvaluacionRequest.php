<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'especialidad' => 'required|string|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_limite' => 'required|date',
            'estado' => 'required|string|in:PENDIENTE,APROBADO,REPROBADO',
            'observaciones' => 'nullable|string|max:1000',
            'teaching_id' => 'nullable|integer|exists:teachings,id',
        ];
    }
}
