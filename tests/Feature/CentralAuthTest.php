<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

test('central login page renders on the main domain', function (): void {
    $this->withServerVariables(['HTTP_HOST' => 'localhost'])->get('/login')
        ->assertOk()
        ->assertSee('Central Admin Login');
});

test('super admin can log in to the central dashboard', function (): void {
    $role = Role::findOrCreate('super_admin', 'web');

    $user = User::query()->create([
        'name' => 'Central Admin',
        'email' => 'central@example.com',
        'password' => 'password123',
    ]);

    $user->assignRole($role);

    $this->withServerVariables(['HTTP_HOST' => 'localhost'])->post('/login', [
        'email' => 'central@example.com',
        'password' => 'password123',
    ])->assertRedirect('/central/dashboard');

    $this->assertAuthenticatedAs($user);
});

test('non super admin central login is rejected with the tenant portal message', function (): void {
    User::query()->create([
        'name' => 'Tenant Staff',
        'email' => 'tenant@example.com',
        'password' => 'password123',
    ]);

    $this->withServerVariables(['HTTP_HOST' => 'localhost'])
        ->from('/login')
        ->post('/login', [
            'email' => 'tenant@example.com',
            'password' => 'password123',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'Tenant accounts must log in through their assigned portal.']);

    $this->assertGuest();
});

test('central logout redirects back to login', function (): void {
    $role = Role::findOrCreate('super_admin', 'web');

    $user = User::query()->create([
        'name' => 'Central Admin',
        'email' => 'central@example.com',
        'password' => 'password123',
    ]);

    $user->assignRole($role);

    $this->actingAs($user)
        ->withServerVariables(['HTTP_HOST' => 'localhost'])
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});
