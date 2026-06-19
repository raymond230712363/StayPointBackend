<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // FITUR REGISTER
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|min:10|max:15' 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), 
            'phone' => $request->phone,
            'role' => 'customer' 
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Register berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    // FITUR LOGIN
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'phone' => 'required|string|min:10' 
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)
                    ->where('phone', $request->phone)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email, password, atau nomor telepon salah.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 200);
    }

    // FITUR LOGIN GOOGLE
    public function loginGoogle(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'google_id' => 'required|string',
        ]);

        $user = User::where('google_id', $request->google_id)
                    ->orWhere('email', $request->email)
                    ->first();

        if (!$user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'google_id' => $request->google_id,
                'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                'role' => 'customer',
                'phone' => null, 
            ]);
        } else {
            if (empty($user->google_id)) {
                $user->google_id = $request->google_id;
                $user->save();
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login via Google berhasil!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 200);
    }

    // FITUR FORGOT PASSWORD
    public function forgotPassword(Request $request) 
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum terdaftar di database StayPoint!'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Kode OTP sukses dikirim!'
        ], 200);
    }

    // FITUR LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout'
        ], 200);
    }

    // FITUR UPDATE PROFILE
    public function updateProfile(Request $request) 
    {
        $request->validate([
            'email' => 'required|email', 
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|min:10|max:15', 
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);
        
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $user->name = $request->name;
            $user->phone = $request->phone;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo'); 
                $path = $file->store('profiles', 'public');
                $user->profile_photo = $path;
            }
            $user->save(); 

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'user' => $user 
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'User dengan email tersebut tidak ditemukan.'
        ], 404);
    }

    // FITUR CHANGE PASSWORD
    public function changePassword(Request $request) 
    {
        $request->validate([
            'email' => 'required|email',
            'current_password' => 'required',
            'new_password' => 'required|min:8', 
        ]);
        
        $user = User::where('email', $request->email)->first(); 

        if ($user) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password lama yang kamu masukkan salah!'
                ], 400);
            }
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password baru berhasil disimpan di DB!'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengubah password, user tidak ditemukan.'
        ], 404);
    }
}