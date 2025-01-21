<?php

namespace Tests\Feature\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserTest extends TestCase
{
    use DatabaseTransactions;

    public static function login_WhenRequestIsIncorrect_ShouldReturnUnprocessableError_Provider(): array
    {
        return [
            ['email', fn () => null, 'The email field is required.'],
            ['email', fn () => 1, 'The email field must be a string.'],
            ['email', fn () => 'test', 'The email field must be a valid email address.'],
            ['email', fn () => Str::random(300). '@yahoo.com', 'The email field must not be greater than 255 characters.'],
            ['email', fn () => Str::random(4). '@yahoo.com', 'The selected email is invalid.'],
            ['password', fn () => null, 'The password field is required.'],
            ['password', fn () => 1, 'The password field must be a string.'],
            ['password', fn () => Str::random(300), 'The password field must not be greater than 255 characters.'],
        ];
    }

    #[Test]
    #[DataProvider('login_WhenRequestIsIncorrect_ShouldReturnUnprocessableError_Provider')]
    public function login_WhenRequestIsIncorrect_ShouldReturnUnprocessableError(string $field, \Closure $value, string $errorMessage): void
    {
        $body = [$field => $value()];
        $response = $this->post(route('api.login'), $body, [
            'Accept' => 'appllication/json'
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors([$field => $errorMessage]);
    }

    #[Test]
    public function login_WhenPasswordIsInvalid_ShouldReturnUnauthorizedError(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'qwerty'], [
            'Accept' => 'appllication/json'
        ]);

        $response->assertUnauthorized()->assertJsonPath('message', 'Email or password is invalid.');
    }

    #[Test]
    public function login_WhenCredentialsIsCorrect_ShouldCreateLoginSessionByToken(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $cookie = $response->getCookie('AuthBjascode_token', false)->getValue();

        $response->assertNoContent()->assertPlainCookie('AuthBjascode_token');
        $this->assertEquals($user->id, PersonalAccessToken::findToken($cookie)->tokenable()->first()->id);
    }

    #[Test]
    public function login_WhenCreatingToken_ShouldSetAbilitiesToAll(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'abilities' => '["*"]'
        ]);
    }

    #[Test]
    public function login_WhenRequestReturnedCookieWithLoginToken_ShouldBeValidForOneDay(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $cookie = $response->getCookie('AuthBjascode_token', false);

        $response->assertNoContent();
        $this->assertEquals($cookie->getExpiresTime(), now()->addDay()->unix());
    }

    #[Test]
    public function login_WhenRequestReturnedCookieWithLoginToken_ShouldHaveSameSiteEqualStrict(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $cookie = $response->getCookie('AuthBjascode_token', false);

        $response->assertNoContent();
        $this->assertEquals($cookie->getSameSite(), 'strict');
    }
}
