<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    /**
     * Os relatórios são acessíveis a qualquer usuário autenticado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação dos filtros do dashboard.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'in:annual,monthly'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }

    /**
     * Mensagens amigáveis para os filtros do relatório.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mode.in' => 'O período deve ser mensal ou anual.',
            'year.integer' => 'O ano informado é inválido.',
            'year.min' => 'O ano deve ser igual ou superior a 2000.',
            'year.max' => 'O ano deve ser igual ou inferior a 2100.',
            'month.integer' => 'O mês informado é inválido.',
            'month.min' => 'O mês deve estar entre 1 e 12.',
            'month.max' => 'O mês deve estar entre 1 e 12.',
        ];
    }

    /**
     * Modo do relatório: anual (padrão) ou mensal.
     */
    public function mode(): string
    {
        return $this->input('mode') === 'monthly' ? 'monthly' : 'annual';
    }

    /**
     * Ano de referência do relatório.
     */
    public function year(): int
    {
        return (int) $this->input('year', Carbon::now()->year);
    }

    /**
     * Mês de referência (utilizado apenas no modo mensal).
     */
    public function month(): int
    {
        return (int) $this->input('month', Carbon::now()->month);
    }

    /**
     * Filtros normalizados para a camada de relatórios.
     *
     * @return array{mode: string, year: int, month: int}
     */
    public function reportFilters(): array
    {
        return [
            'mode' => $this->mode(),
            'year' => $this->year(),
            'month' => $this->month(),
        ];
    }
}
