<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'matric_no' => 'A180123',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect(auth()->user()->matric_no)->toBe('A180123');
});

test('registration requires a matric number', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['matric_no' => 'The matric no field is required.']);

    $this->assertGuest();
});

test('registration rejects a matric number that is already taken', function () {
    User::factory()->create(['matric_no' => 'A180123']);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'matric_no' => 'A180123',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['matric_no' => 'The matric no has already been taken.']);

    $this->assertGuest();
});
