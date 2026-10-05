<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TransitionIssueHttpRequest extends FormRequest
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
        return ['to_status_id' => ['required', 'uuid']];
    }

    public function getActorId(): string
    {
        return (string) $this->user()->id;
    }
}
