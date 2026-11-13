<?php

declare(strict_types=1);

namespace App\Modules\DashboardCatalog\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TableauDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi dilakukan di route middleware (can:dashboards.create / can:dashboards.edit)
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'number' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'name' => ['required', 'string', 'max:255'],
            'definition' => ['nullable', 'string', 'max:5000'],
            'contents' => ['nullable', 'string', 'max:5000'],
            'purpose' => ['nullable', 'string', 'max:100'],
            'tableau_project' => ['nullable', 'string', 'max:150'],
            'mapping' => ['nullable', 'string', 'max:100'],
            'user_permission' => ['nullable', 'string', 'max:5000'],
            'user_mapping' => ['nullable', 'string', 'max:100'],
            'owner' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'number' => __('number'),
            'name' => __('dashboard name'),
            'definition' => __('definition'),
            'contents' => __('contents'),
            'purpose' => __('purpose'),
            'tableau_project' => __('Tableau project'),
            'mapping' => __('mapping'),
            'user_permission' => __('user permission'),
            'user_mapping' => __('user mapping'),
            'owner' => __('owner'),
            'notes' => __('notes'),
        ];
    }
}
