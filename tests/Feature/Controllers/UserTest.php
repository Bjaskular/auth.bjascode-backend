<?php

namespace Tests\Feature\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function example(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);
        Sanctum::actingAs($user);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $cookie = $response->getCookie('AuthBjascode_token', false)->getValue();

        $response->assertNoContent()->assertPlainCookie('AuthBjascode_token');
        $this->assertEquals($user->id, PersonalAccessToken::findToken($cookie)->tokenable()->first()->id);
    }
}
