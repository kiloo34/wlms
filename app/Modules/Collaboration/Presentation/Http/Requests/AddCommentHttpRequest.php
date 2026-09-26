<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorize in controller via Gate or just assume authenticated
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'uuid', 'exists:comments,id'],
        ];
    }
}

