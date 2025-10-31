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

        $response = postJson('/api/tasks', [
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

        $response = postJson('/api/tasks', [
            'description' => 'Description without title',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    test('title must not exceed 255 characters', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/tasks', [
            'title' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    test('description is optional', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/tasks', [
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

        $response = postJson('/api/tasks', [
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

        $response = getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    });

    test('users only see their own tasks', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Task::factory()->count(3)->for($user1)->create();
        Task::factory()->count(2)->for($user2)->create();

        Sanctum::actingAs($user1);
        $response = getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    });

    test('empty list returns empty array', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = getJson('/api/tasks');

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

        $response = getJson('/api/tasks');

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

        $response = getJson("/api/tasks/{$task->id}");

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
        $response = getJson("/api/tasks/{$task->id}");

        $response->assertStatus(404);
    });

    test('viewing non-existent task returns 404', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = getJson('/api/tasks/99999');

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

        $response = putJson("/api/tasks/{$task->id}", [
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
        $response = putJson("/api/tasks/{$task->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(404);
    });

    test('title is required for updates when provided', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create();

        $response = putJson("/api/tasks/{$task->id}", [
            'title' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    test('status can be updated', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->pending()->create();

        $response = putJson("/api/tasks/{$task->id}", [
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

        $response = putJson("/api/tasks/{$task->id}", [
            'status' => Task::STATUS_COMPLETED,
        ]);

        $response->assertStatus(200);

        $task->refresh();
        expect($task->completed_at)->not->toBeNull();
    });
});

describe('Task Deletion', function () {
    test('users can delete their own tasks', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create();

        $response = deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);
    });

    test('users cannot delete others tasks', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $task = Task::factory()->for($user2)->create();

        Sanctum::actingAs($user1);
        $response = deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(404);
    });

    test('deleting task returns 204', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create();

        $response = deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);
        $response->assertNoContent();
    });

    test('deleted task is removed from database', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->for($user)->create();
        $taskId = $task->id;

        deleteJson("/api/tasks/{$taskId}");

        assertDatabaseMissing('tasks', ['id' => $taskId]);
    });
});

describe('Authorization', function () {
    test('all endpoints require authentication', function () {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        getJson('/api/tasks')->assertStatus(401);
        postJson('/api/tasks', ['title' => 'Test'])->assertStatus(401);
        getJson("/api/tasks/{$task->id}")->assertStatus(401);
        putJson("/api/tasks/{$task->id}", ['title' => 'Test'])->assertStatus(401);
        deleteJson("/api/tasks/{$task->id}")->assertStatus(401);
    });

    test('unauthenticated requests are rejected', function () {
        $response = getJson('/api/tasks');

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
        expect(getJson('/api/tasks')->json())->toHaveCount(2);

        Sanctum::actingAs($user2);
        expect(getJson('/api/tasks')->json())->toHaveCount(3);

        Sanctum::actingAs($user3);
        expect(getJson('/api/tasks')->json())->toHaveCount(1);
    });
});

