<?php

namespace App\Services;

use App\Http\Exceptions\UnauthorizedExcpetion;
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

    public function login(array $requestArray): NewAccessToken
    {
        $user = $this->repository->where('email', $requestArray['email'])->first();

        if (! Hash::check($requestArray['password'], $user->password)) {
            throw new UnauthorizedExcpetion('Email or password is invalid.');
        }

        return $user->createToken($user->getHashEmail(), ['*'], now()->addSeconds((int) config('session.lifetime')));
    }
}
