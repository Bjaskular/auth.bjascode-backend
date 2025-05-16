<?php

namespace Tests\Feature\Controllers;

use App\Enums\AuthCookieName;
use App\Enums\TokenAbility;
use App\Models\Application;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\UserApplication;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
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
            [ProviderData::createInstance(field: 'redirect_key', key: 'redirect_key', value: 1, errorMessage: 'validation.string')],
            [ProviderData::createInstance(field: 'redirect_key', key: 'redirect_key', value: Str::random(51), errorMessage: 'validation.max.string')],
            [ProviderData::createInstance(field: 'redirect_key', key: 'redirect_key', value: 'xyz', errorMessage: 'validation.exists')],
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

        $response->assertUnauthorized()->assertJsonPath('message', 'validation.login_failed');
    }

    #[Test]
    public function login_WhenCredentialsIsCorrect_ShouldCreateLoginSessionByTokens(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $responseBody = json_decode($response->baseResponse->getContent(), true)['data'];

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data', 3)
                    ->has('data.access_token', 4)
                    ->has('data.refresh_token', 4)
                    ->hasAll([
                        'data.access_token.name',
                        'data.access_token.value',
                        'data.access_token.ttl',
                        'data.access_token.expired_at',
                        'data.refresh_token.name',
                        'data.refresh_token.value',
                        'data.refresh_token.ttl',
                        'data.refresh_token.expired_at',
                        'data.redirect_url',
                    ])
                    ->whereAll([
                        'data.access_token.name' => AuthCookieName::API_ACCESS->value,
                        'data.access_token.ttl' => 3600,
                        'data.access_token.expired_at' => now()->addHour()->unix(),
                        'data.refresh_token.name' => AuthCookieName::REFRESH->value,
                        'data.refresh_token.ttl' => 604800,
                        'data.refresh_token.expired_at' => now()->addWeek()->unix(),
                        'data.redirect_url' => null,
                    ])
            );

        $this->assertEquals($user->id, PersonalAccessToken::findToken($responseBody['access_token']['value'])->tokenable()->first()->id);
        $this->assertEquals($user->id, PersonalAccessToken::findToken($responseBody['refresh_token']['value'])->tokenable()->first()->id);
    }

    #[Test]
    public function login_WhenRequestHasRedirectKey_ShouldReturnRedirectUrlInResponse(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);
        $application = Application::factory()->create();
        UserApplication::factory()
            ->for($user)
            ->for($application)
            ->create();

        $body = [
            'redirect_key' => $application->key,
            'email' => $user->email,
            'password' => 'zaq1@WSX'
        ];

        $response = $this->post(route('api.login'), $body, [
            'Accept' => 'appllication/json'
        ]);

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data', 3)
                    ->whereAll(['data.redirect_url' => $application->url])
            );
    }

    #[Test]
    public function login_WhenCreatingToken_ShouldCreateTwoAbilitiesForLoginUser(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'abilities' => '["access-api"]'])
            ->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'abilities' => '["refresh-access-token"]']);
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
            'Authorization' => 'Bearer '. $this->getToken(
                $user,
                'refresh_token',
                [TokenAbility::REFRESH_ACCESS_TOKEN->value]
            )
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
            'Authorization' => 'Bearer '. $this->getToken(
                $user,
                'refresh_token',
                [TokenAbility::REFRESH_ACCESS_TOKEN->value],
                now()->subDay()
            )
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

        $responseBody = json_decode($response->baseResponse->getContent(), true)['data'];

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data', 4)
                    ->hasAll([
                        'data.name',
                        'data.value',
                        'data.ttl',
                        'data.expired_at',
                    ])
                    ->whereAll([
                        'data.name' => AuthCookieName::API_ACCESS->value,
                        'data.ttl' => 3600,
                        'data.expired_at' => now()->addHour()->unix(),
                    ])
            );

        $this->assertEquals($user->id, PersonalAccessToken::findToken($responseBody['value'])->tokenable()->first()->id);
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

        $response->assertOk();
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

        $response->assertOk();
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

        $response->assertOk();
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'abilities' => '["access-api"]']);
    }

    #[Test]
    public function me_WhenUserHasAccessToApplication_ShouldReturnAccessInfo(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()
            ->create(['password' => 'zaq1@WSX']);

        $application = Application::factory()
            ->create(['key' => 'admin']);

        UserApplication::factory()
            ->for($user)
            ->for($application)
            ->create();

        $query = ['key' => $application->key];
        $response = $this->get(route('api.me', $query), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken(
                $user,
                'access-token',
                [TokenAbility::ACCESS_API->value]
            )
        ]);

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data', 3)
                    ->whereAll([
                        'data.id' => $user->id,
                        'data.application_key' => $application->key,
                    ])
            );

        $encryptSecret = json_decode($response->baseResponse->getContent(), true)['data']['application_secret'];

        $this->assertEquals($application->secret, Crypt::decryptString($encryptSecret));
    }

    #[Test]
    public function me_WhenTokenIsExpired_ShouldReturnUnauthorizedError(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()
            ->create(['password' => 'zaq1@WSX']);

        $application = Application::factory()
            ->create(['key' => 'admin']);

        UserApplication::factory()
            ->for($user)
            ->for($application)
            ->create();

        $query = ['key' => $application->key];
        $response = $this->get(route('api.me', $query), [
            'Accept' => 'appllication/json',
            'Authorization' => 'Bearer '. $this->getToken(
                $user,
                'access-token',
                [TokenAbility::ACCESS_API->value],
                now()->subDay()
            )
        ]);

        $response->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    #[Test]
    public function me_WhenUserDoesntHaveAccessToApplication_ShouldReturnForbiddenError(): void
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
            'Authorization' => 'Bearer '. $this->getToken(
                $user,
                'access-token',
                [TokenAbility::ACCESS_API->value]
            )
        ]);

        $response->assertForbidden()->assertJsonPath('message', 'validation.forbidden_access');
    }
}
