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
            'especialidad' => 'nullable|string|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_limite' => 'nullable|date',
            'estado' => 'nullable|string|in:PENDIENTE,APROBADO,REPROBADO',
            'teaching_id' => 'nullable|integer|exists:teachings,id',
        ];
    }
}
