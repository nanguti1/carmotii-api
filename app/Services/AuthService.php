<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        try {
            $validator = Validator::make($data, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone_number' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'bio' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone_number' => $data['phone_number'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'bio' => $data['bio'] ?? null,
                'verification_status' => 'pending',
                'is_active' => true,
                'is_banned' => false,
            ]);

            // Assign default role
            $user->assignRole('user');

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Login user
     */
    public function login(array $credentials): array
    {
        try {
            $validator = Validator::make($credentials, [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['Your account has been deactivated.'],
                ]);
            }

            if ($user->is_banned) {
                throw ValidationException::withMessages([
                    'email' => ['Your account has been banned.'],
                ]);
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        try {
            $validator = Validator::make($data, [
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'bio' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user->update($data);

            return $user->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
