<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

describe('User Registration', function () {
    test('users can register with valid data', function () {
        $response = postJson('/api/register', [
            'name' => 'J',
            'email' => 'john@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('registration requires valid email format', function () {
        $response = postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('registration requires unique email', function () {
        User::factory()->create(['email' => 'john@gmail.com']);

        $response = postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('registration requires password with minimum 8 characters', function () {
        $response = postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@gmail.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('registration requires matching password confirmation', function () {
        $response = postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('passwords are hashed when stored', function () {
        postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john.test@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'john.test@gmail.com')->first();

        expect($user->password)->not->toBe('password123');
        expect(password_verify('password123', $user->password))->toBeTrue();
    });
});

describe('User Login', function () {
    test('users can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'john.login@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'john.login@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);
    });

    test('login fails with invalid email', function () {
        $response = postJson('/api/login', [
            'email' => 'nonexistent@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'message' => 'The provided credentials are incorrect.',
            ]);
    });

    test('login fails with invalid password', function () {
        User::factory()->create([
            'email' => 'john.wrong@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'john.wrong@gmail.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'message' => 'The provided credentials are incorrect.',
            ]);
    });

    test('login generates sanctum token', function () {
        $user = User::factory()->create([
            'email' => 'john.token@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'john.token@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        expect($response->json('token'))->toBeString();
        expect($user->tokens()->count())->toBe(1);
    });
});

describe('User Logout', function () {
    test('authenticated users can logout', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);
    });

    test('logout revokes current access token', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        postJson('/api/logout');

        // Token should be revoked
        expect($user->tokens()->count())->toBe(0);
    });

    test('logout requires authentication', function () {
        $response = postJson('/api/logout');

        $response->assertStatus(401);
    });
});

describe('Protected Routes', function () {
    test('authenticated users can access profile', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.profile@gmail.com',
        ]);

        Sanctum::actingAs($user);

        $response = getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => 'John Doe',
                'email' => 'john.profile@gmail.com',
            ]);
    });

    test('unauthenticated users cannot access profile', function () {
        $response = getJson('/api/user');

        $response->assertStatus(401);
    });

    test('profile does not expose sensitive data', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonMissing(['password'])
            ->assertJsonMissing(['remember_token']);
    });
});
