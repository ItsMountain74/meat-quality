<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends ApiController
{
    public function store(LoginRequest $request)
    {
        /** @var \App\Models\User|null $user */
        $user = User::query()->where('email', $request->string('email')->lower()->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return $this->error('Invalid credentials.', 422);
        }

        $deviceName = $request->string('device_name')->toString() ?: 'mobile';
        $token = $user->createToken($deviceName.'-'.Str::random(6))->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Logged in successfully.');
    }
}

