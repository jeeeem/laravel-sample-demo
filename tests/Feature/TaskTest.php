<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('Task Creation', function () {
    test('authenticated users can create tasks', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/v1/tasks', [
            'title' => 'Test Task',
            'description' => 'This is a test task',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'title', 'description', 'status', 'created_at', 'updated_at'])
            ->assertJson([
                'title' => 'Test Task',
                'description' => 'This is a test task',
                'status' => Task::STATUS_PENDING,
            ]);

        assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'user_id' => $user->id,
        ]);
    });

    test('title is required', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/v1/tasks', [
            'description' => 'Description without title',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    test('title must not exceed 255 characters', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/v1/tasks', [
            'title' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    test('description is optional', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/v1/tasks', [
            'title' => 'Task without description',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Task without description',
                'description' => null,
            ]);
    });

    test('status defaults to pending', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/v1/tasks', [
            'title' => 'New Task',
        ]);

        $response->assertStatus(201)
            ->assertJson(['status' => Task::STATUS_PENDING]);
    });
});

describe('Task Listing', function () {
    test('users can view their own tasks', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Task::factory()->count(3)->for($user)->create();

        $response = getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    });

    test('users only see their own tasks', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Task::factory()->count(3)->for($user1)->create();
        Task::factory()->count(2)->for($user2)->create();

        Sanctum::actingAs($user1);
        $response = getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    });

    test('empty list returns empty array', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    });

    test('tasks are returned with proper structure', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Task::factory()->for($user)->create([
            'title' => 'Sample Task',
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $response = getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'title', 'description', 'status', 'completed_at', 'created_at', 'updated_at'],
            ]);
    });
});

describe('Task Viewing', function () {
    test('users can view their own task details', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create([
            'title' => 'My Task',
        ]);

        $response = getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $task->id,
                'title' => 'My Task',
            ]);
    });

    test('users cannot view others tasks', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $task = Task::factory()->for($user2)->create();

        Sanctum::actingAs($user1);
        $response = getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(404);
    });

    test('viewing non-existent task returns 404', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = getJson('/api/v1/tasks/99999');

        $response->assertStatus(404);
    });
});

describe('Task Updating', function () {
    test('users can update their own tasks', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create([
            'title' => 'Original Title',
        ]);

        $response = putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $task->id,
                'title' => 'Updated Title',
                'description' => 'Updated description',
            ]);

        assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
        ]);
    });

    test('users cannot update others tasks', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $task = Task::factory()->for($user2)->create();

        Sanctum::actingAs($user1);
        $response = putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(404);
    });

    test('title is required for updates when provided', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create();

        $response = putJson("/api/v1/tasks/{$task->id}", [
            'title' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    test('status can be updated', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->pending()->create();

        $response = putJson("/api/v1/tasks/{$task->id}", [
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => Task::STATUS_IN_PROGRESS]);

        assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_IN_PROGRESS,
        ]);
    });

    test('completed status sets completed_at timestamp', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->pending()->create();

        $response = putJson("/api/v1/tasks/{$task->id}", [
            'status' => Task::STATUS_COMPLETED,
        ]);

        $response->assertStatus(200);

        $task->refresh();
        expect($task->completed_at)->not->toBeNull();
    });

    test('changing status from completed clears completed_at timestamp', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a completed task
        $task = Task::factory()->for($user)->completed()->create();
        expect($task->completed_at)->not->toBeNull();

        // Change status back to in_progress
        $response = putJson("/api/v1/tasks/{$task->id}", [
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $response->assertStatus(200);

        $task->refresh();
        expect($task->completed_at)->toBeNull();
        expect($task->status)->toBe(Task::STATUS_IN_PROGRESS);
    });

    test('changing status from completed to pending clears completed_at', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a completed task
        $task = Task::factory()->for($user)->completed()->create();

        // Change status to pending
        $response = putJson("/api/v1/tasks/{$task->id}", [
            'status' => Task::STATUS_PENDING,
        ]);

        $response->assertStatus(200);

        $task->refresh();
        expect($task->completed_at)->toBeNull();
        expect($task->status)->toBe(Task::STATUS_PENDING);
    });

    test('observer handles status changes without manual intervention', function () {
        $user = User::factory()->create();

        // Create task
        $task = Task::factory()->for($user)->pending()->create();
        expect($task->completed_at)->toBeNull();

        // Update directly via model (not API) to test observer
        $task->update(['status' => Task::STATUS_COMPLETED]);
        expect($task->completed_at)->not->toBeNull();

        // Change back
        $task->update(['status' => Task::STATUS_PENDING]);
        expect($task->completed_at)->toBeNull();
    });
});

describe('Task Deletion', function () {
    test('users can delete their own tasks', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create();

        $response = deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(204);
    });

    test('users cannot delete others tasks', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $task = Task::factory()->for($user2)->create();

        Sanctum::actingAs($user1);
        $response = deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(404);
    });

    test('deleting task returns 204', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create();

        $response = deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(204);
        $response->assertNoContent();
    });

    test('deleted task is removed from database', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create();
        $taskId = $task->id;

        deleteJson("/api/v1/tasks/{$taskId}");

        assertDatabaseMissing('tasks', ['id' => $taskId]);
    });
});

describe('Authorization', function () {
    test('all endpoints require authentication', function () {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        getJson('/api/v1/tasks')->assertStatus(401);
        postJson('/api/v1/tasks', ['title' => 'Test'])->assertStatus(401);
        getJson("/api/v1/tasks/{$task->id}")->assertStatus(401);
        putJson("/api/v1/tasks/{$task->id}", ['title' => 'Test'])->assertStatus(401);
        deleteJson("/api/v1/tasks/{$task->id}")->assertStatus(401);
    });

    test('unauthenticated requests are rejected', function () {
        $response = getJson('/api/v1/tasks');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    });

    test('multiple users have isolated task lists', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Task::factory()->count(2)->for($user1)->create();
        Task::factory()->count(3)->for($user2)->create();
        Task::factory()->count(1)->for($user3)->create();

        Sanctum::actingAs($user1);
        expect(getJson('/api/v1/tasks')->json())->toHaveCount(2);

        Sanctum::actingAs($user2);
        expect(getJson('/api/v1/tasks')->json())->toHaveCount(3);

        Sanctum::actingAs($user3);
        expect(getJson('/api/v1/tasks')->json())->toHaveCount(1);
    });
});
