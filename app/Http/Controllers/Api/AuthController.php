<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ProfileRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    use ApiResponse;

    //FITUR REGISTER
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Enkripsi password bcrypt
            'phone' => $request->phone,
            'role' => 'customer' 
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->resolve(),
        ], 'Register berhasil', 201);
    }

    // FITUR LOGIN
    public function login(LoginRequest $request)
    {
        // Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // Cek apakah user ada dan password-nya benar
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Email atau password salah', 401);
        }

        // Buat token baru setelah sukses login
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->resolve(),
        ], 'Login berhasil');
    }
    public function forgotPassword(Request $request) 
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // 1. Cek apakah email ada di DB
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error('Email belum terdaftar di database StayPoint!', 404);
        }

        // Struktur placeholder untuk pengiriman OTP/link reset password.
        // Balikin JSON sukses
        return $this->success(null, 'Kode OTP sukses dikirim!');
    }

    public function me(Request $request)
    {
        return $this->success(new UserResource($request->user()));
    }

    public function updateProfile(ProfileRequest $request)
    {
        $data = $request->validated();
        unset($data['profile_photo']);
        $request->user()->update($data);

        return $this->success(new UserResource($request->user()->refresh()), 'Profile updated');
    }

    public function uploadProfilePhoto(ProfileRequest $request)
    {
        if (!$request->hasFile('profile_photo')) {
            return $this->error('Profile photo is required', 422, ['profile_photo' => ['The profile photo field is required.']]);
        }

        Storage::disk('public')->delete($request->user()->profile_photo);

        $request->user()->update([
            'profile_photo' => $request->file('profile_photo')->store('profiles', 'public'),
        ]);

        return $this->success(new UserResource($request->user()->refresh()), 'Profile photo uploaded');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        if (!Hash::check($request->current_password, $request->user()->password)) {
            return $this->error('Current password is incorrect', 422, [
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success(null, 'Password changed');
    }

    public function googleLogin(Request $request)
    {
        return $this->success([
            'provider' => 'google',
            'implemented' => false,
            'expected_payload' => [
                'google_id' => 'string',
                'email' => 'string',
                'name' => 'string',
            ],
        ], 'Google login placeholder');
    }

    // FITUR LOGOUT
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan saat ini
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Berhasil logout');
    }
}
