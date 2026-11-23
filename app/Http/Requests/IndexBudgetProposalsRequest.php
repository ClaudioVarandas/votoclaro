<?php

namespace App\Http\Requests;

use App\Enums\BudgetProposalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBudgetProposalsRequest extends FormRequest
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
            'tema' => ['nullable', 'string', 'max:150'],
            'partido' => ['nullable', 'string', 'max:10'],
            'estado' => ['nullable', Rule::enum(BudgetProposalStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado' => 'O estado escolhido não existe.',
            'q.max' => 'A pesquisa pode ter até :max caracteres.',
        ];
    }
}
