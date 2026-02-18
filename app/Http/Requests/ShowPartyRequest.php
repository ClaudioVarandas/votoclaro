<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowPartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'position' => ['nullable', 'in:favor,contra,abstencao'],
            'type' => ['nullable', 'string', 'max:5'],
            'search' => ['nullable', 'string', 'max:200'],
            'sort' => ['nullable', 'in:date,result'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'authored_status' => ['nullable', 'in:approved,rejected,in_progress'],
            'authored_type' => ['nullable', 'string', 'max:5'],
            'authored_search' => ['nullable', 'string', 'max:200'],
            'authored_sort' => ['nullable', 'in:date,status'],
            'authored_direction' => ['nullable', 'in:asc,desc'],
            'authored_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
