<?php

use App\Models\User;

test('user factory produces valid data', function () {
    $user = User::factory()->create();

    expect($user->first_name)->toBeString()
        ->and($user->email)->toContain('@');

    // date_of_birth is optional (70% chance), so it might be null
    if ($user->date_of_birth) {
        expect($user->date_of_birth)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    } else {
        expect($user->date_of_birth)->toBeNull();
    }
});

test('full name attribute handles missing middle names correctly', function () {
    // Case 1: All names present
    $user = User::factory()->make([
        'first_name' => 'John',
        'middle_names' => 'Quincy',
        'last_name' => 'Doe',
    ]);
    expect($user->full_name)->toBe('John Quincy Doe');

    // Case 2: Middle name is null (The array_filter test)
    $user->middle_names = null;
    expect($user->full_name)->toBe('John Doe'); // No double spaces!
});

test('initials are generated correctly and uppercase', function () {
    $user = User::factory()->make([
        'first_name' => 'mansoer',
        'last_name' => 'gallie',
    ]);

    expect($user->initials())->toBe('MG');
});

test('age and minor logic works correctly', function () {
    // Test Adult
    $adult = User::factory()->make([
        'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
    ]);
    expect($adult->getAgeAttribute())->toBe(25)
        ->and($adult->isMinor())->toBeFalse();

    // Test Minor using the factory state we created
    $minor = User::factory()->minor()->make();

    expect($minor->isMinor())->toBeTrue();
});

test('two factor state applies correctly', function () {
    $user = User::factory()->withTwoFactor()->create();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
});
