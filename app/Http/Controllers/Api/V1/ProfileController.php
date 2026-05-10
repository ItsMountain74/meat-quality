<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ProfileController extends ApiController
{
    public function show(Request $request)
    {
        return $this->success(new UserResource($request->user()), 'Profile fetched successfully.');
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = $request->user();

        $payload = $request->validated();

        if (array_key_exists('email', $payload)) {
            $payload['email'] = strtolower($payload['email']);
        }

        $user->fill($payload)->save();

        return $this->success(new UserResource($user->fresh()), 'Profile updated successfully.');
    }
}

