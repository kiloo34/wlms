<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignIssueHttpRequest extends FormRequest
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
        return ['assignee_id' => ['nullable']];
    }

    public function getActorId(): string
    {
        return (string) $this->user()->id;
    }
}
