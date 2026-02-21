<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hosts\GetTokenRequest;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function token(GetTokenRequest $request)
    {
        $user = User::where('name', $request->name)->select('*')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw new AuthenticationException('Неверные имя или пароль!');
        }

        $token = $user->createToken(
            $request->name,
            $user->is_admin ? ['rename-host'] : []
        )->plainTextToken;

        return $this->sendSuccess(['access_token' => $token]);
    }
}
