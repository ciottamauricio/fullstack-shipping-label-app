<?php

namespace App\Actions;

use App\Models\User;

class RegisterUserAction
{
    public function execute(array $data): array
    {
        $user = User::create($data);
        $token = $user->createToken('api')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }
}
