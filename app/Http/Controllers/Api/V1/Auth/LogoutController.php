<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\Request;

class LogoutController extends ApiController
{
    public function store(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success((object) [], 'Logged out successfully.');
    }
}

