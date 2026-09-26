<?php
declare(strict_types=1);
namespace App\Modules\Workload\Presentation\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-rbac');
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => 'required|string|max:50',
            'slug' => ['required', 'string', 'max:50', Rule::unique('statuses')->ignore($id)],
            'category' => 'required|string|max:20|in:TODO,IN_PROGRESS,DONE',
            'color' => 'nullable|string|max:20',
        ];
    }
}
