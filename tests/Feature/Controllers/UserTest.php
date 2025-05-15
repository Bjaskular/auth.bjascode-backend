<?php

namespace Tests\Feature\Controllers;

use App\Enums\TokenAbility;
use App\Models\Application;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\UserApplication;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Tools\Providers\ProviderData;

final class UserTest extends TestCase
{
    use DatabaseTransactions;

    public static function login_WhenRequestIsIncorrect_ShouldReturnUnprocessableError_Provider(): array
    {
        return [
            [ProviderData::createInstance(field: 'email', key: 'email', value: null, errorMessage: 'validation.required')],
            [ProviderData::createInstance(field: 'email', key: 'email', value: 1, errorMessage: 'validation.string')],
            [ProviderData::createInstance(field: 'email', key: 'email', value: 'test', errorMessage: 'validation.email')],
            [ProviderData::createInstance(field: 'email', key: 'email', value: Str::random(300), errorMessage: 'validation.max.string')],
            [ProviderData::createInstance(field: 'email', key: 'email', value: Str::random(4). '@yahoo.com', errorMessage: 'validation.exists')],
            [ProviderData::createInstance(field: 'password', key: 'password', value: null, errorMessage: 'validation.required')],
            [ProviderData::createInstance(field: 'password', key: 'password', value: 1, errorMessage: 'validation.string')],
            [ProviderData::createInstance(field: 'password', key: 'password', value: Str::random(300), errorMessage: 'validation.max.string')],
        ];
    }

    private function getToken(User $user, string $name = 'access_token', array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): string
    {
        $expiresAt ??= now()->addSecond();
        return $user->createToken($name, $abilities, $expiresAt)->plainTextToken;
    }

    #[Test]
    #[DataProvider('login_WhenRequestIsIncorrect_ShouldReturnUnprocessableError_Provider')]
    public function login_WhenRequestIsIncorrect_ShouldReturnUnprocessableError(ProviderData $data): void
    {
        $body = [$data->field => $data->value];
        $response = $this->post(route('api.login'), $body, [
            'Accept' => 'appllication/json'
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors([$data->key => $data->errorMessage]);
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
    public function login_WhenCredentialsIsCorrect_ShouldCreateLoginSessionByTokens(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $cookieAccess = $response->getCookie('auth-bjascode-access-token', false)->getValue();
        $cookieRefresh = $response->getCookie('auth-bjascode-refresh-token', false)->getValue();

        $response->assertNoContent()->assertPlainCookie('auth-bjascode-access-token');
        $response->assertNoContent()->assertPlainCookie('auth-bjascode-refresh-token');
        $this->assertEquals($user->id, PersonalAccessToken::findToken($cookieAccess)->tokenable()->first()->id);
        $this->assertEquals($user->id, PersonalAccessToken::findToken($cookieRefresh)->tokenable()->first()->id);
    }

    #[Test]
    public function login_WhenCreatingToken_ShouldCreateTwoAbilitiesForLoginUser(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'abilities' => '["access-api"]']);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'abilities' => '["refresh-access-token"]']);
    }

    #[Test]
    public function login_WhenRequestReturnedCookiesWithLoginTokens_ShouldBeValidLifeTimes(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $cookieAccess = $response->getCookie('auth-bjascode-access-token', false);
        $cookieRefresh = $response->getCookie('auth-bjascode-refresh-token', false);

        $response->assertNoContent();
        $this->assertEquals($cookieAccess->getExpiresTime(), now()->addHour()->unix());
        $this->assertEquals($cookieRefresh->getExpiresTime(), now()->addDays(7)->unix());
    }

    #[Test]
    public function login_WhenRequestReturnedCookiesWithLoginToken_ShouldHaveSameSiteEqualStrict(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $cookieAccess = $response->getCookie('auth-bjascode-access-token', false);
        $cookieRefresh = $response->getCookie('auth-bjascode-refresh-token', false);

        $response->assertNoContent();
        $this->assertEquals($cookieAccess->getSameSite(), 'strict');
        $this->assertEquals($cookieRefresh->getSameSite(), 'strict');
    }

    #[Test]
    public function logout_WhenRequestIsCorrect_ShouldLogoutUser(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->delete(route('api.logout'), [], [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken($user)
        ]);

        $response->assertNoContent();
    }

    #[Test]
    public function logout_WhenTokenIsInvalid_ShouldReturnUnauthenticatedError(): void
    {
        $response = $this->delete(route('api.logout'), [], [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer test'
        ]);

        $response->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    #[Test]
    public function logout_WhenTokenDoesntHaveAccessAbilities_ShouldReturnForbiddenError(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->delete(route('api.logout'), [], [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken($user, 'refresh_token', [TokenAbility::ACCESS_API->value])
        ]);

        $response->assertForbidden()->assertJsonPath('message', 'Invalid ability provided.');
    }

    #[Test]
    public function refreshToken_WhenTokenIsInvalid_ShouldReturnUnauthenticatedError(): void
    {
        $response = $this->get(route('api.refresh_token'), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer test'
        ]);

        $response->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    #[Test]
    public function refreshToken_WhenTokenDoesntHaveRefreshAbilities_ShouldForbiddenReturnError(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->get(route('api.refresh_token'), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken(
                user: $user,
                abilities: [TokenAbility::ACCESS_API->value]
            )
        ]);

        $response->assertForbidden()->assertJsonPath('message', 'Invalid ability provided.');
    }

    #[Test]
    public function refreshToken_WhenRefreshTokenIsExpired_ShouldReturnUnauthorizedError(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->get(route('api.refresh_token'), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken($user, 'refresh_token', [TokenAbility::REFRESH_ACCESS_TOKEN->value], now()->subDay())
        ]);

        $response->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    #[Test]
    public function refreshToken_WhenRefreshedToken_ShouldCreateNewAccessToken(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);
        $this->getToken($user, 'access_token');

        $response = $this->get(route('api.refresh_token'), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken($user, 'refresh_token')
        ]);

        $cookieAccess = $response->getCookie('auth-bjascode-access-token', false)->getValue();

        $response->assertNoContent()->assertPlainCookie('auth-bjascode-access-token');
        $this->assertEquals($user->id, PersonalAccessToken::findToken($cookieAccess)->tokenable()->first()->id);
    }

    #[Test]
    public function refreshToken_WhenRefreshedToken_ShouldDeletedOldAccessToken(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);
        $oldToken = $this->getToken($user, 'access_token');

        $response = $this->get(route('api.refresh_token'), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken($user, 'refresh_token')
        ]);

        $response->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertDatabaseMissing('personal_access_tokens', ['token' => $oldToken]);
    }

    #[Test]
    public function refreshToken_WhenRefreshedToken_ShouldDeletedOldAccessTokenOnlyForRequestUser(): void
    {
        /** @var \App\Models\User $user */
        $users = User::factory()->count(2)->create(['password' => 'zaq1@WSX']);
        $refreshToken = $this->getToken($users[0], 'refresh_token');
        $this->getToken($users[0], 'access_token');
        $this->getToken($users[1], 'access_token');

        $response = $this->get(route('api.refresh_token'), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $refreshToken
        ]);

        $response->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 3);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $users[0]->id, 'name' => 'access_token']);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $users[0]->id, 'name' => 'refresh_token']);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $users[1]->id, 'name' => 'access_token']);
    }

    #[Test]
    public function refreshToken_WhenCreatingNewAccessToken_ShouldCreateAbilityForAccess(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->get(route('api.refresh_token'), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken($user, 'refresh_token')
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'abilities' => '["access-api"]']);
    }

    #[Test]
    public function refreshToken_WhenRequestReturnedCookiesWithAccessTokens_ShouldBeValidLifeTimes(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->get(route('api.refresh_token'), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken($user, 'refresh_token')
        ]);

        $cookieAccess = $response->getCookie('auth-bjascode-access-token', false);

        $response->assertNoContent();
        $this->assertEquals($cookieAccess->getExpiresTime(), now()->addHour()->unix());
    }

    #[Test]
    public function refreshToken_WhenRequestReturnedCookiesWithAccessToken_ShouldHaveSameSiteEqualStrict(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->get(route('api.refresh_token'), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken($user, 'refresh_token')
        ]);

        $cookieAccess = $response->getCookie('auth-bjascode-access-token', false);

        $response->assertNoContent();
        $this->assertEquals($cookieAccess->getSameSite(), 'strict');
    }

    #[Test]
    public function me_test(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);
        $applications = Application::factory()
            ->sequence(
                ['key' => 'admin'],
                ['key' => 'account'],
            )
            ->count(2)
            ->create();

        UserApplication::factory()
            ->for($user)
            ->create(['application_id' => $applications[0]->id]);

        $query = ['key' => $applications[1]->key];
        $response = $this->get(route('api.me', $query), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken($user, 'refresh_token')
        ]);

        $response->assertNoContent();
    }
}
