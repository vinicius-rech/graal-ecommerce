<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_admin_can_authenticate_using_the_login_screen(): void
    {
        $adminRole = Role::factory()->create([
            'name' => 'admin'
        ]);

        $faker = Faker::create();

        $adminUser = User::factory()->create([
            'name' => $faker->name,
            'email' => $faker->email,
            'role_id' => $adminRole->id,
            'password' => Hash::make('password')
        ]);

        $response = $this->actingAs($adminUser)
            ->post('/login', [
                'email' => $adminUser->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($adminUser);

        $response->assertRedirect(RouteServiceProvider::ADMIN_DASHBOARD);
    }

    public function test_customer_can_authenticate_using_the_login_screen(): void
    {
        $guestRole = Role::factory()->create([
            'name' => 'guest'
        ]);

        $faker = Faker::create();

        $customerUser = User::factory()->create([
            'name' => $faker->name,
            'email' => $faker->email,
            'role_id' => $guestRole->id,
            'password' => Hash::make('password')
        ]);

        $response = $this->actingAs($customerUser)
            ->post('/login', [
                'email' => $customerUser->email,
                'password' => 'password'
            ]);

        $this->assertAuthenticatedAs($customerUser);

        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_user_with_invalid_email_cannot_authenticate_using_the_login_screen(): void
    {
        $faker = Faker::create();
        $response = $this->post(RouteServiceProvider::LOGIN, [
            'email' => $faker->email,
            'password' => $faker->password
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors(['email']);
    }

    public function test_user_with_invalid_password_cannot_authenticate_using_the_login_screen(): void
    {
        $faker = Faker::create();

        $user = User::factory()->create([
            'email' => $faker->email,
            'password' => Hash::make('password')
        ]);

        $response = $this->post(RouteServiceProvider::LOGIN, [
            'email' => $user->email,
            'password' => 'wrong_password'
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors('email');
    }
}
