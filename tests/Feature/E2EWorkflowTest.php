<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\{assertDatabaseCount, assertDatabaseHas, assertDatabaseMissing, getJson, postJson, putJson, deleteJson};

describe('Complete User Journey', function () {
    test('user can complete full workflow from registration to task management', function () {
        // Step 1: Register a new user
        $registerResponse = postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john.doe.test@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registerResponse->assertStatus(201);
        expect($registerResponse->json())->toHaveKeys(['user', 'token']);
        
        $userId = $registerResponse->json('user.id');
        $user = User::find($userId);

        // Authenticate as the user for subsequent requests
        Sanctum::actingAs($user);

        // Step 2: Verify user can access profile
        $profileResponse = getJson('/api/user');

        $profileResponse->assertStatus(200)
            ->assertJson([
                'id' => $userId,
                'email' => 'john.doe.test@gmail.com',
                'name' => 'John Doe',
            ]);

        // Step 3: Create multiple tasks
        $task1 = postJson('/api/tasks', [
            'title' => 'Complete project documentation',
            'description' => 'Write API docs',
            'status' => Task::STATUS_PENDING,
        ]);

        $task1->assertStatus(201);
        $task1Id = $task1->json('id');

        $task2 = postJson('/api/tasks', [
            'title' => 'Review pull requests',
            'description' => 'Check team PRs',
        ]);

        $task2->assertStatus(201);
        $task2Id = $task2->json('id');

        $task3 = postJson('/api/tasks', [
            'title' => 'Setup CI/CD pipeline',
        ]);

        $task3->assertStatus(201);
        $task3Id = $task3->json('id');

        // Step 4: List all tasks
        $listResponse = getJson('/api/tasks');

        $listResponse->assertStatus(200)
            ->assertJsonCount(3);

        // Step 5: Update task status to in_progress
        $updateResponse = putJson("/api/tasks/{$task1Id}", [
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'id' => $task1Id,
                'status' => Task::STATUS_IN_PROGRESS,
            ]);

        // Step 6: Complete a task
        $completeResponse = putJson("/api/tasks/{$task1Id}", [
            'status' => Task::STATUS_COMPLETED,
        ]);

        $completeResponse->assertStatus(200)
            ->assertJson([
                'id' => $task1Id,
                'status' => Task::STATUS_COMPLETED,
            ]);

        expect($completeResponse->json('completed_at'))->not->toBeNull();

        // Step 7: View specific task details
        $viewResponse = getJson("/api/tasks/{$task2Id}");

        $viewResponse->assertStatus(200)
            ->assertJson([
                'id' => $task2Id,
                'title' => 'Review pull requests',
                'description' => 'Check team PRs',
            ]);

        // Step 8: Delete a task
        $deleteResponse = deleteJson("/api/tasks/{$task3Id}");

        $deleteResponse->assertStatus(204);

        assertDatabaseMissing('tasks', ['id' => $task3Id]);

        // Step 9: Verify task list after deletion
        $finalListResponse = getJson('/api/tasks');

        $finalListResponse->assertStatus(200)
            ->assertJsonCount(2);

        // Step 10: Verify logout functionality (tested in AuthTest)
        $logoutResponse = postJson('/api/logout');

        $logoutResponse->assertStatus(200);

        // Final verification: Check database state
        assertDatabaseHas('users', [
            'email' => 'john.doe.test@gmail.com',
            'name' => 'John Doe',
        ]);

        assertDatabaseCount('tasks', 2); // Only 2 tasks remain after deletion

        assertDatabaseHas('tasks', [
            'id' => $task1Id,
            'user_id' => $userId,
            'status' => Task::STATUS_COMPLETED,
        ]);

        assertDatabaseHas('tasks', [
            'id' => $task2Id,
            'user_id' => $userId,
            'status' => Task::STATUS_PENDING,
        ]);
    });
});

describe('Multi-User Workflow', function () {
    test('multiple users have completely isolated task management', function () {
        // Create two users
        $user1 = User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice.test@gmail.com',
        ]);

        $user2 = User::factory()->create([
            'name' => 'Bob',
            'email' => 'bob.test@gmail.com',
        ]);

        // User 1 creates tasks
        Sanctum::actingAs($user1);

        $alice1 = postJson('/api/tasks', [
            'title' => 'Alice Task 1',
            'description' => 'Alice work',
        ]);

        $alice1->assertStatus(201);
        $aliceTaskId = $alice1->json('id');

        postJson('/api/tasks', [
            'title' => 'Alice Task 2',
        ])->assertStatus(201);

        // User 2 creates tasks
        Sanctum::actingAs($user2);

        $bob1 = postJson('/api/tasks', [
            'title' => 'Bob Task 1',
            'description' => 'Bob work',
        ]);

        $bob1->assertStatus(201);
        $bobTaskId = $bob1->json('id');

        postJson('/api/tasks', [
            'title' => 'Bob Task 2',
        ])->assertStatus(201);

        postJson('/api/tasks', [
            'title' => 'Bob Task 3',
        ])->assertStatus(201);

        // Verify User 1 only sees their own tasks
        Sanctum::actingAs($user1);

        $alice_list = getJson('/api/tasks');

        $alice_list->assertStatus(200)
            ->assertJsonCount(2);

        // Each task should contain Alice's name
        $alice_list->assertJsonFragment(['title' => 'Alice Task 1'])
            ->assertJsonFragment(['title' => 'Alice Task 2']);

        // Verify User 2 only sees their own tasks
        Sanctum::actingAs($user2);

        $bob_list = getJson('/api/tasks');

        $bob_list->assertStatus(200)
            ->assertJsonCount(3);

        // Each task should contain Bob's name
        $bob_list->assertJsonFragment(['title' => 'Bob Task 1'])
            ->assertJsonFragment(['title' => 'Bob Task 2'])
            ->assertJsonFragment(['title' => 'Bob Task 3']);

        // Verify User 1 cannot view User 2's task
        Sanctum::actingAs($user1);

        $unauthorized_view = getJson("/api/tasks/{$bobTaskId}");

        $unauthorized_view->assertStatus(404);

        // Verify User 2 cannot update User 1's task
        Sanctum::actingAs($user2);

        $unauthorized_update = putJson("/api/tasks/{$aliceTaskId}", [
            'title' => 'Hacked title',
        ]);

        $unauthorized_update->assertStatus(404);

        // Verify User 1 cannot delete User 2's task
        Sanctum::actingAs($user1);

        $unauthorized_delete = deleteJson("/api/tasks/{$bobTaskId}");

        $unauthorized_delete->assertStatus(404);

        // Verify task still exists in database
        assertDatabaseHas('tasks', [
            'id' => $bobTaskId,
            'user_id' => $user2->id,
        ]);

        // Final database verification
        assertDatabaseCount('users', 2);
        assertDatabaseCount('tasks', 5); // 2 Alice + 3 Bob

        assertDatabaseHas('tasks', ['user_id' => $user1->id]);
        assertDatabaseHas('tasks', ['user_id' => $user2->id]);
    });
});
