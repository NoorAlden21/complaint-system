<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\User\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function index()
    {
        $users = $this->userService->list();
        return UserResource::collection($users)
            ->response();
    }

    public function show(int $userId)
    {
        $user = $this->userService->findById($userId);
        return (new UserResource($user))->response();
    }

    public function showByEmail(string $userEmail)
    {
        $user = $this->userService->findByEmail($userEmail);
        return (new UserResource($user))->response();
    }
}
