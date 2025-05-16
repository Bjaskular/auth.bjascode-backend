<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\AuthorizationRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Resources\AuthenticationRefreshResource;
use App\Http\Resources\AuthenticationResource;
use App\Http\Resources\AuthorizationResource;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly IAuthService $authService)
    {
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $userAccess = $this->authService->login($request->validated());

        return (new AuthenticationResource($userAccess))->response();
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function refreshToken(): JsonResponse
    {
        $accessToken = $this->authService->refreshAccessToken();

        return (new AuthenticationRefreshResource($accessToken))->response();
    }

    public function me(AuthorizationRequest $request): JsonResponse
    {
        $access = $this->authService->authorize($request->validated());

        return (new AuthorizationResource($access))->response();
    }
}
