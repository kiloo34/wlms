<?php

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LogWorkHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'time_spent_seconds' => 'required|integer|min:1',
            'description' => 'required|string',
            'started_at' => 'required|date',
        ];
    }
}
