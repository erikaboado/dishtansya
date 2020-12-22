<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_register_a_user()
    {
        $user = [
            'email' => 'backend@multisyscorp.com',
            'password' => 'test123'
        ];

        $this->post('api/register', $user)->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'backend@multisyscorp.com']);
    }

    /** @test */
    public function cannot_register_existing_email()
    {
        $juan = [
            'email' => 'backend@multisyscorp.com',
            'password' => 'test123'
        ];

        $this->post('api/register', $juan)->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'backend@multisyscorp.com']);

        $jane = [
            'email' => 'backend@multisyscorp.com',
            'password' => 'test123'
        ];

        $this->post('api/register', $jane)->assertStatus(400);
    }

    /** @test */
    public function registered_user_can_login()
    {   
        $juan = [
            'email' => 'backend@multisyscorp.com',
            'password' => 'test123'
        ];

        User::create($juan);

        $this->post('api/login', $juan)->assertStatus(201);
    }

    /** @test */
    public function unregistered_user_cannot_login()
    {   
        $user = [
            'email' => 'backend@multisyscorp.com',
            'password' => 'test123'
        ];

        $this->post('api/login', $user)->assertStatus(401);
    }
}
