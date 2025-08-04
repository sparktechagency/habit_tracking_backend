<?php 

namespace App\Services;

use App\Models\User;

class AuthService
{
    public function createUser(array $data)
    {
        return User::create($data);
    }
}