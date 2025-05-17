<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\AuthorizationRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Resources\AccessesResource;
use App\Http\Resources\AuthenticationRefreshResource;
use App\Http\Resources\AuthenticationResource;
use App\Http\Resources\AuthorizationResource;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Authorization
 */
class AuthController extends Controller
{
    private readonly IAuthService $authService;

    public function __construct(IAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * POST: Login user
     *
     * Returns access and refresh tokens with redirect url.
     *
     * @bodyParam redirect_key string Example: admin
     * @bodyParam email string required Example: test1@wp.pl
     * @bodyParam password string required Example: zaq1@WSX
     *
     * @responseFile app/DocResponses/auth_controller_login.json
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        $userAccess = $this->authService->login($request->validated());

        return (new AuthenticationResource($userAccess))->response();
    }

    /**
     * DELETE: Logout user
     *
     * Deletes access and refresh token user.
     *
     * @header Authorization Bearer {access_token}
     *
     * @response 204
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * GET: Refresh access token
     *
     * Returns new access token
     *
     * @header Authorization Bearer {refresh_token}
     *
     * @responseFile app/DocResponses/auth_controller_refreshToken.json
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(): JsonResponse
    {
        $accessToken = $this->authService->refreshAccessToken();

        return (new AuthenticationRefreshResource($accessToken))->response();
    }

    /**
     * GET: Authorize access user
     *
     * Check if auth user has access to specific app.
     *
     * @header Authorization Bearer {access_token}
     *
     * @responseFile app/DocResponses/auth_controller_authorize.json
     * @return \Illuminate\Http\JsonResponse
     */
    public function authorize(AuthorizationRequest $request): JsonResponse
    {
        $access = $this->authService->authorize($request->validated());

        return (new AuthorizationResource($access))->response();
    }

    /**
     * GET: Accesses user
     *
     * Returns accesses user.
     *
     * @header Authorization Bearer {access_token}
     *
     * @responseFile app/DocResponses/auth_controller_me.json
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(): JsonResponse
    {
        $accesses = $this->authService->getAccesses();

        return (new AccessesResource($accesses))->response();
    }
}
