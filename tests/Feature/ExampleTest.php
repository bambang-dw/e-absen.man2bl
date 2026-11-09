<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login_from_protected_pages_and_actions(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->post(route('dashboard.pulang-awal'))
            ->assertRedirect(route('login'));
    }
}
