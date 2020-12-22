<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6'
        ], [
            'email.unique' => 'Email already taken'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        User::create($validator->validated());

        return response()->json([
            'message' => 'User successfully registered'
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();
        if ($user && $this->isLocked($user->id)) {
            return response()->json(['message' => 'Account is locked'], 403);
        }

        if ($token = $this->guard()->attempt($validator->validated())) {
            $this->resetFailedLoginAttempts($user->id);
            return response()->json(['access_token' => $token], 201);
        }

        if ($user) {
            $this->updateFailedAttempts($user->id);
        }
        return response()->json(['message' => 'Invalid credentials'], 401);

    }

    protected function isLocked($id)
    {
        $user = User::findOrFail($id);
        if($user && $user->last_failed_attempt != null) {
            $now = Carbon::now()->toDateTimeString();
            if($now >= $user->last_failed_attempt) {
                $user->last_failed_attempt = null;
                $user->login_attempts = 0;
                $user->save();
                return false;
            }
            return true;
        }
        return false;
    }

    protected function updateFailedAttempts($id)
    {
        $user = User::findOrFail($id);
        
        if($user && $user->login_attempts < 5) {
            $user->login_attempts += 1;
            $user->save();
        }

        if($user && $user->login_attempts >= 5) {
            $this->lockAccount($user->id);
        }

    }

    protected function lockAccount($id)
    {
        $unlock_time = Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s');
        $user = User::findOrFail($id);
        $user->last_failed_attempt = $unlock_time;
        $user->save();
    }

    protected function resetFailedLoginAttempts($id)
    {
        $user = User::findOrFail($id);
        $user->last_failed_attempt = null;
        $user->login_attempts = 0;
        $user->save();
    }

     /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}
