<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root route redirects rather than rendering a page directly —
     * mirrors the CI4 app's role-based redirect closure in
     * app/Config/Routes.php (guests go to the login screen).
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin/login');
    }
}
