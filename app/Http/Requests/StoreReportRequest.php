<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReportRequest extends FormRequest
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
            'user_id'     => ['required', 'integer', 'min:1', 'exists:users,id'],
            'period_from' => ['required', 'date', 'before_or_equal:period_to'],
            'period_to'   => ['required', 'date', 'after_or_equal:period_from'],
        ];
    }
}
