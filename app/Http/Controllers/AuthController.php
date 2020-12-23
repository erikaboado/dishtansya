<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * User Registration
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * User Authentication
     *
     * @param Request $request
     * @return JsonResponse
     */
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
            $this->updateLoginAttempts($user->id);
        }
        return response()->json(['message' => 'Invalid credentials'], 401);

    }

    /**
     * Check if account is locked
     *
     * @param $id
     * @return bool
     */
    protected function isLocked($id)
    {
        $user = User::findOrFail($id);

        if ($user && $user->last_failed_attempt != null) {
            if (Carbon::now()->toDateTimeString() >= $user->last_failed_attempt) {
                $user->last_failed_attempt = null;
                $user->login_attempts = 0;
                $user->save();
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Method that updates login attempts
     *
     * @param $id
     */
    protected function updateLoginAttempts($id)
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

    /**
     * Method that locks user account
     *
     * @param $id
     */
    protected function lockAccount($id)
    {
        $unlock_time = Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s');
        $user = User::findOrFail($id);
        $user->last_failed_attempt = $unlock_time;
        $user->save();
    }

    /**
     * Method that reset failed login attempts
     *
     * @param $id
     */
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
    * @return Guard
    */
    public function guard()
    {
        return Auth::guard();
    }
}
