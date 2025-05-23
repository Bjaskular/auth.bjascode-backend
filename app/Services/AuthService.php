<?php

namespace App\Services;

use App\Enums\TokenAbility;
use App\Http\Exceptions\ForbiddenException;
use App\Http\Exceptions\UnauthorizedException;
use App\Models\User;
use App\Repositories\Interfaces\IApplicationRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\Abstracts\Service;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

/** @property \App\Repositories\Interfaces\IUserRepository $repository*/
class AuthService extends Service implements IAuthService
{
    private readonly IApplicationRepository $applicationRepository;

    public function __construct(
        Request $request,
        IUserRepository $userRepository,
        IApplicationRepository $applicationRepository,
    ) {
        parent::__construct($request, $userRepository);
        $this->applicationRepository = $applicationRepository;
    }

    public function login(array $requestArray): array
    {
        $user = $this->repository->where('email', $requestArray['email'])->first();

        if (! Hash::check($requestArray['password'], $user->password)) {
            throw new UnauthorizedException(__('validation.login_failed'));
        }

        $user->tokens()
            ->where('name', 'access_token')
            ->orWhere('name', 'refresh_token')
            ->delete();

        $accessToken = $user->createToken(
            'access_token',
            [TokenAbility::ACCESS_API->value],
            now()->addMinutes(config('sanctum.access_expiration'))
        );

        $refreshToken = $user->createToken(
            'refresh_token',
            [TokenAbility::REFRESH_ACCESS_TOKEN->value],
            now()->addMinutes(config('sanctum.refresh_expiration'))
        );

        $appUrl = ! is_null($requestArray['redirect_key'] ?? null)
            ? $this->applicationRepository
                ->select(['url'])
                ->where('key', $requestArray['redirect_key'])
                ->first()
                ->url
            : null;

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'redirect_url' => $appUrl
        ];
    }

    public function logout(): void
    {
        $this->request->user()->tokens()->delete();
    }

    public function refreshAccessToken(): NewAccessToken
    {
        $user = $this->request->user();

        $user->tokens()->where('name', 'access_token')->delete();

        return $user->createToken(
            'access_token',
            [TokenAbility::ACCESS_API->value],
            now()->addMinutes(config('sanctum.access_expiration'))
        );
    }

    public function authorize(array $requestArray): User
    {
        $user = $this->repository
            ->with('application', fn ($query) => $query->select(['key', 'secret'])->where('key', $requestArray['key']))
            ->whereHas('application', fn ($query) => $query->where('key', $requestArray['key']))
            ->where('users.id', $this->request->user()->id)
            ->first();

        if (! $user) {
            throw new ForbiddenException(__('validation.forbidden_access'));
        }

        return $user;
    }

    public function getAccesses(): User
    {
        return $this->repository
            ->with('applications', fn ($query) => $query->select(['key', 'name', 'url']))
            ->where('users.id', $this->request->user()->id)
            ->first();
    }
}
