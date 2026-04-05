<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->all());

            return response()->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $result['user']->id,
                    'first_name' => $result['user']->first_name,
                    'last_name' => $result['user']->last_name,
                    'email' => $result['user']->email,
                    'phone_number' => $result['user']->phone_number,
                    'verification_status' => $result['user']->verification_status,
                    'roles' => $result['user']->getRoleNames(),
                ],
                'token' => $result['token'],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->all());

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $result['user']->id,
                    'first_name' => $result['user']->first_name,
                    'last_name' => $result['user']->last_name,
                    'email' => $result['user']->email,
                    'phone_number' => $result['user']->phone_number,
                    'verification_status' => $result['user']->verification_status,
                    'roles' => $result['user']->getRoleNames(),
                ],
                'token' => $result['token'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Login failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout successful'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'date_of_birth' => $user->date_of_birth,
                    'profile_image' => $user->profile_image,
                    'bio' => $user->bio,
                    'verification_status' => $user->verification_status,
                    'is_active' => $user->is_active,
                    'is_banned' => $user->is_banned,
                    'roles' => $user->getRoleNames(),
                    'created_at' => $user->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch user data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->updateProfile($request->user(), $request->all());

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'date_of_birth' => $user->date_of_birth,
                    'profile_image' => $user->profile_image,
                    'bio' => $user->bio,
                    'verification_status' => $user->verification_status,
                    'is_active' => $user->is_active,
                    'is_banned' => $user->is_banned,
                    'roles' => $user->getRoleNames(),
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
