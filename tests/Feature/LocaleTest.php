<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('allows guest to change locale and stores in session', function () {
    $response = post(route('locale.update'), [
        'locale' => 'id'
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    
    expect(session('locale'))->toBe('id');
});

it('allows authenticated user to change locale, storing in session and database', function () {
    $user = UserModel::factory()->create([
        'locale' => 'en'
    ]);

    $response = actingAs($user)->post(route('locale.update'), [
        'locale' => 'id'
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    expect(session('locale'))->toBe('id');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'locale' => 'id'
    ]);
});

it('rejects invalid locale payload with validation errors', function () {
    $response = post(route('locale.update'), [
        'locale' => 'fr'
    ]);

    $response->assertSessionHasErrors(['locale']);
    $response->assertStatus(302); // Redirect back on validation error
});
