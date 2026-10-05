<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteIssueHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var UserModel|null $user */
        $user = $this->user();

        return $user && $user->hasPermission('issues:manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
