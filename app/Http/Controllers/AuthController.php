<?php

namespace App\Http\Controllers;

use App\Data\AuthResponseData;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\Interfaces\ApiResponseBuilderInterface;
use App\Data\UserData;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly ApiResponseBuilderInterface $responseBuilder
    ) {}

    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Check if user already exists
            if (User::where('email', $request->email)->exists()) {
                return $this->responseBuilder->validationError(
                    ['email' => ['The email has already been taken.']],
                    'Validation failed'
                );
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            $authResponse = AuthResponseData::from([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => UserData::from([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            ]);

            return $this->responseBuilder->success($authResponse, 201);

        } catch (\Exception $e) {
            return $this->responseBuilder->error(
                'registration_failed',
                'Registration failed',
                500
            );
        }
    }

    /**
     * Login user
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Validate credentials
            if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return $this->responseBuilder->unauthorized(
                    'invalid_credentials',
                    'Invalid credentials'
                );
            }

            $user = User::where('email', $request->email)->firstOrFail();

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            $authResponse = AuthResponseData::from([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => UserData::from([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            ]);

            return $this->responseBuilder->success($authResponse);

        } catch (\Exception $e) {
            return $this->responseBuilder->error(
                'login_failed',
                'Login failed',
                500
            );
        }
    }

    /**
     * Logout user (revoke token)
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $token = Auth::user()->currentAccessToken();
            
            if ($token) {
                $token->delete();
            }

            return $this->responseBuilder->success([
                'message' => 'Successfully logged out'
            ]);

        } catch (\Exception $e) {
            return $this->responseBuilder->error(
                'logout_failed',
                'Logout failed',
                500
            );
        }
    }

    /**
     * Get authenticated user
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = Auth::user();

            $userData = UserData::from([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

            return $this->responseBuilder->success($userData);

        } catch (\Exception $e) {
            return $this->responseBuilder->error(
                'user_retrieval_failed',
                'Failed to retrieve user',
                500
            );
        }
    }
}
