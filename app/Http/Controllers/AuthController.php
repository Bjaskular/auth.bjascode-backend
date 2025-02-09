<?php

namespace App\Http\Controllers;

use App\Enums\AuthCookieName;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    private readonly IAuthService $authService;

    public function __construct(IAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $userAccess = $this->authService->login($request->validated());

        return response()
            ->json(null, Response::HTTP_NO_CONTENT)
            ->withCookie(Cookie::make(
                name: AuthCookieName::API_ACCESS->value,
                value: $userAccess['access_token']->plainTextToken,
                minutes: config('sanctum.access_expiration', 60),
                sameSite: config('session.same_site', 'strict')
            ))
            ->withCookie(Cookie::make(
                name: AuthCookieName::REFRESH->value,
                value: $userAccess['refresh_token']->plainTextToken,
                minutes: config('sanctum.refresh_expiration', 60 * 24 * 7),
                sameSite: config('session.same_site', 'strict')
            ));
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function refreshToken(): JsonResponse
    {
        $accessToken = $this->authService->refreshAccessToken();

        return response()
            ->json(null, Response::HTTP_NO_CONTENT)
            ->withCookie(Cookie::make(
                name: AuthCookieName::API_ACCESS->value,
                value: $accessToken->plainTextToken,
                minutes: config('sanctum.access_expiration', 60),
                sameSite: config('session.same_site', 'strict')
            ));
    }

    public function me(): JsonResponse
    {
        return response()->json();
    }
}
