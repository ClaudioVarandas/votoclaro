<?php

namespace App\Http\Requests;

use App\Enums\BudgetProposalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBudgetArticlesRequest extends FormRequest
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
            'estado' => ['nullable', Rule::enum(BudgetProposalStatus::class)],
            'lei' => ['nullable', 'boolean'],
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
            'lei' => 'O filtro das leis alteradas só aceita sim ou não.',
            'q.max' => 'A pesquisa pode ter até :max caracteres.',
        ];
    }
}
