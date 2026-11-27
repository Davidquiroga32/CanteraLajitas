<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_admin_can_update_own_profile_information(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_seller_sees_own_data_without_edit_form(): void
    {
        $user = User::factory()->create(['name' => 'Gilberto Pineda']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Gilberto Pineda');
        $response->assertSee('pídele al administrador', false);
        $response->assertDontSee('Datos personales');
        $response->assertSee('Cambiar contraseña');
    }

    public function test_seller_cannot_change_own_name_or_email(): void
    {
        $user = User::factory()->create(['name' => 'Gilberto Pineda']);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Otro Nombre',
            'email' => 'otro@example.com',
        ]);

        $response->assertForbidden();
        $this->assertSame('Gilberto Pineda', $user->refresh()->name);
    }
}
