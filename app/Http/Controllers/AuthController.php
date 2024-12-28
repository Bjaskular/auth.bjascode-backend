<?php

namespace App\Http\Controllers;

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
        $token = $this->authService->login($request->validated());

        return response()
            ->json([], Response::HTTP_NO_CONTENT)
            ->withCookie(Cookie::make(
                name: config('app.name'). '_token',
                value: $token->plainTextToken,
                minutes: config('session.lifetime', 1440),
                sameSite: config('session.same_site', 'strict')
            ));
    }

    public function logout(): JsonResponse
    {
        return response()->json();
    }

    public function refreshToken(): JsonResponse
    {
        return response()->json();
    }

    public function me(): JsonResponse
    {
        return response()->json();
    }
}
