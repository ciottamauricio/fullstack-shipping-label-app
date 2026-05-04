<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    public function execute(array $credentials): array
    {
        if (! Auth::attempt($credentials)) {
            throw new \InvalidArgumentException('Invalid credentials.');
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('api')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }
}
