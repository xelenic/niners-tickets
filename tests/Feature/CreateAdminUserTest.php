<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it creates an admin who can log in', function () {
    $this->artisan('app:create-admin-user', ['--name' => 'Event Admin', '--email' => 'Admin@Example.com'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    $admin = User::sole();

    expect($admin->name)->toBe('Event Admin')
        ->and($admin->email)->toBe('admin@example.com')
        ->and($admin->is_admin)->toBeTrue()
        ->and(Hash::check('a-strong-password', $admin->password))->toBeTrue();
});

test('it refuses an email address that is already registered', function () {
    User::factory()->create(['email' => 'admin@example.com']);

    $this->artisan('app:create-admin-user', ['--name' => 'Event Admin', '--email' => 'admin@example.com'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsOutputToContain('The email has already been taken.')
        ->assertFailed();

    expect(User::count())->toBe(1);
});

test('it refuses a password shorter than 8 characters', function () {
    $this->artisan('app:create-admin-user', ['--name' => 'Event Admin', '--email' => 'admin@example.com'])
        ->expectsQuestion('Password', 'short')
        ->expectsOutputToContain('The password field must be at least 8 characters.')
        ->assertFailed();

    expect(User::count())->toBe(0);
});
