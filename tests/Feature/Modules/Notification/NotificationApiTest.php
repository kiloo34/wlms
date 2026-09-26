<?php

namespace Tests\Feature\Modules\Notification;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel as User;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access notifications', function () {
    $response = $this->getJson('/api/notifications');
    $response->assertStatus(401);

    $response = $this->putJson('/api/notifications/read');
    $response->assertStatus(401);
});

test('user can get their own notifications and see unread count', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create 3 notifications for user
    NotificationModel::create([
        'id' => Str::uuid(),
        'user_id' => $user->id,
        'type' => 'issue.assigned',
        'data' => ['issue_number' => 123]
    ]);
    NotificationModel::create([
        'id' => Str::uuid(),
        'user_id' => $user->id,
        'type' => 'comment.added',
        'data' => ['body_preview' => 'Hello']
    ]);
    
    // Read notification
    NotificationModel::create([
        'id' => Str::uuid(),
        'user_id' => $user->id,
        'type' => 'issue.transitioned',
        'data' => [],
        'read_at' => now(),
    ]);

    // Other user notification
    NotificationModel::create([
        'id' => Str::uuid(),
        'user_id' => $otherUser->id,
        'type' => 'issue.assigned',
        'data' => ['issue_number' => 999]
    ]);

    $response = $this->actingAs($user)->getJson('/api/notifications');

    $response->assertStatus(200)
        ->assertJsonPath('unread_count', 2)
        ->assertJsonCount(3, 'data'); // Total 3 for this user

    // Ensure other user's notification is not returned
    $data = $response->json('data');
    foreach ($data as $item) {
        expect($item['user_id'] ?? $user->id)->toBe($user->id);
        if ($item['type'] === 'issue.assigned') {
            expect($item['data']['issue_number'])->not->toBe(999);
        }
    }
});

test('user can mark all their notifications as read', function () {
    $user = User::factory()->create();

    NotificationModel::create([
        'id' => Str::uuid(),
        'user_id' => $user->id,
        'type' => 'issue.assigned',
        'data' => ['issue_number' => 123]
    ]);

    $response = $this->actingAs($user)->putJson('/api/notifications/read');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Notifications marked as read.'
        ]);

    $this->assertDatabaseMissing('notifications', [
        'user_id' => $user->id,
        'read_at' => null
    ]);
});
