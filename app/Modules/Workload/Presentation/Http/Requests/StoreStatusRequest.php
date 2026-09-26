<?php
declare(strict_types=1);
namespace App\Modules\Workload\Presentation\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-rbac'); // Or a generic admin permission
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:statuses,slug',
            'category' => 'required|string|max:20|in:TODO,IN_PROGRESS,DONE',
            'color' => 'nullable|string|max:20',
        ];
    }
}
