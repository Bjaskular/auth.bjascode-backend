<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
// use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_example(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['password' => 'zaq1@WSX']);
        // Sanctum::actingAs($user);

        $response = $this->post(route('api.login'), ['email' => $user->email, 'password' => 'zaq1@WSX'], [
            'Accept' => 'appllication/json'
        ]);

        $response->dump();
        dd();
    }
}
