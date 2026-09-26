<?php
declare(strict_types=1);
namespace App\Modules\Workload\Presentation\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
final class CreateIssueHttpRequest extends FormRequest
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
            'project_id'    => ['sometimes', 'required', 'uuid'],
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'issue_type_id' => ['required', 'uuid'],
            'priority_id'   => ['required', 'uuid'],
            'sprint_id'     => ['nullable', 'uuid'],
            'original_estimate_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }
    public function getIdempotencyKey(): string { return (string) ($this->header('Idempotency-Key') ?? Str::uuid()); }
    public function getActorId(): string { return (string) $this->user()->id; }
}
