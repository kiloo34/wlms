<?php
declare(strict_types=1);
namespace Tests\Feature\Modules\Collaboration;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Domain\Events\IssueCreated;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_created_when_issue_created_event_dispatched()
    {
        $user = UserModel::factory()->create();
        
        $issueId = Str::uuid()->toString();
        $projectId = Str::uuid()->toString();
        
        $event = new IssueCreated(
            $issueId,
            $projectId,
            "1",
            "New Issue",
            (string) $user->id,
            new \DateTimeImmutable()
        );
        
        event($event);
        
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => 'issue',
            'auditable_id' => $issueId,
            'event' => 'created',
            'actor_id' => (string) $user->id,
        ]);
        
        $log = \App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\AuditLogModel::first();
        $this->assertEquals("New Issue", $log->new_values['title']);
    }
}
