<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $r = $this->post('/register', [
            'name' => 'Test Advocate',
            'email' => 'test@aarambhax.test',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);
        $r->assertRedirect('/app');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@aarambhax.test']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@aarambhax.test',
            'password' => bcrypt('secret12345'),
        ]);

        $r = $this->post('/login', [
            'email' => 'login@aarambhax.test',
            'password' => 'secret12345',
        ]);
        $r->assertRedirect('/app');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_rejected(): void
    {
        $r = $this->post('/login', [
            'email' => 'nope@aarambhax.test',
            'password' => 'wrong',
        ]);
        $r->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authed_user_can_access_app_dashboard(): void
    {
        $user = User::factory()->create();
        $r = $this->actingAs($user)->get('/app');
        $r->assertStatus(200);
        $r->assertSee('Welcome back');
    }
}
