<?php

namespace App\Services;

use App\Enums\TokenAbility;
use App\Http\Exceptions\ForbiddenException;
use App\Http\Exceptions\UnauthorizedException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Abstracts\Service;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

/** @property \App\Repositories\Interfaces\IUserRepository $repository*/
class AuthService extends Service implements IAuthService
{
    public function __construct(Request $request, UserRepository $userRepository)
    {
        parent::__construct($request, $userRepository);
    }

    public function login(array $requestArray): array
    {
        $user = $this->repository->where('email', $requestArray['email'])->first();

        if (! Hash::check($requestArray['password'], $user->password)) {
            throw new UnauthorizedException('Email or password is invalid.');
        }

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

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
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
            ->with('application', fn ($query) => $query->where('key', $requestArray['key']))
            ->whereHas('application', fn ($query) => $query->where('key', $requestArray['key']))
            ->where('users.id', $this->request->user()->id)
            ->first();

        dd($user);

        if (! $user) {
            throw new ForbiddenException("You don't have access.");
        }

        return $user;
    }
}
