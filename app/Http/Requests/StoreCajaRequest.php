<?php

namespace App\Http\Requests;

use App\Rules\CajaCerradaRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'saldo_inicial' => ['required', 'numeric', 'min:1', new CajaCerradaRule],
            'ubicacione_id' => ['nullable', 'integer', 'exists:ubicaciones,id'],
        ];
    }
}
