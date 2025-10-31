<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * Endpoints for user authentication including registration, login, and logout.
 * All authenticated endpoints require a Bearer token in the Authorization header.
 *
 * Rate Limiting:
 * - Register: 3 attempts per minute per IP address
 * - Login: 5 attempts per minute per IP address
 */
class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * Creates a new user account and immediately issues an API Bearer token for authentication.
     * This endpoint allows new users to sign up for the application. Upon successful registration,
     * the user receives their account details and a token they can use for subsequent API requests.
     *
     * Rate limit: 3 attempts per minute per IP address to prevent abuse.
     *
     * @response 201 {
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   },
     *   "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz"
     * }
     *
     * @response 422 {
     *   "message": "The email has already been taken.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     *
     * @response 429 {
     *   "message": "Too Many Attempts."
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // Auto-hashed by User model
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

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
     * Rate limit: 5 attempts per minute per IP address to prevent brute-force attacks.
     *
     * @response 200 {
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   },
     *   "token": "2|XyZaBcDeFgHiJkLmNoPqRsTuVw"
     * }
     *
     * @response 422 {
     *   "message": "The provided credentials are incorrect.",
     *   "errors": {
     *     "email": ["The provided credentials are incorrect."]
     *   }
     * }
     *
     * @response 429 {
     *   "message": "Too Many Attempts."
     * }
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
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "Logged out successfully"
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
