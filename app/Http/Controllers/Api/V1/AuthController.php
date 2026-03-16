<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponses;

class AuthController extends Controller
{
    use ApiResponses;
    public function login(LoginUserRequest $request)
    {
        $request->validated();

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return $this->error('Invalid credentials', 401);
        }

        $user = User::where('email', $request->email)->first();

        return $this->ok(
            'Authenticated',
            [
                'token' => $user->createToken('API Token')->plainTextToken
            ]
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok('Logged out');
    }
}
