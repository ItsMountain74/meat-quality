<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Str;

class RegisterController extends ApiController
{
    public function store(RegisterRequest $request)
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        $deviceName = $request->string('device_name')->toString() ?: 'mobile';

        $token = $user->createToken($deviceName.'-'.Str::random(6))->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Registered successfully.', 201);
    }
}

