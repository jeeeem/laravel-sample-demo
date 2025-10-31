<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Endpoints for user authentication including registration, login, and logout.
 * All authenticated endpoints require a Bearer token in the Authorization header.
 *
 * Rate Limiting:
 * - Register: 10 attempts per minute per IP address
 * - Login: 10 attempts per minute per IP address
 */
#[Group('Authentication', weight: 1)]
class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * Creates a new user account and immediately issues an API Bearer token for authentication.
     * This endpoint allows new users to sign up for the application. Upon successful registration,
     * the user receives their account details and a token they can use for subsequent API requests.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // Auto-hashed by User model
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        /**
         * Newly created user with authentication token.
         *
         * @status 201
         *
         * @body array{user: UserResource, token: string}
         */
        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Login an existing user
     *
     * Authenticates a user with their email and password, then issues an API Bearer token.
     * This endpoint verifies the user's credentials and, if valid, returns their account
     * details along with a fresh authentication token for making authenticated API requests.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        /**
         * User details with authentication token.
         *
         * @body array{user: UserResource, token: string}
         */
        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Logout the authenticated user
     *
     * Revokes the current API Bearer token, effectively logging out the user from this device/session.
     * This endpoint requires authentication and will delete only the token used in the request,
     * allowing the user to remain logged in on other devices if they have multiple active tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        /**
         * Success message confirming logout.
         *
         * @body array{message: string}
         */
        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
