<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddIssueCommentHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel $user */
        $user = $this->user();
        return $user && $user->hasPermission('issues:manage');
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
