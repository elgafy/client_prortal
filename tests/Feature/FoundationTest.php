<?php

use App\Models\Client;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\SettingSeeder;

test('public registration is disabled', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('users have roles with internal/client helpers', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $staff = User::factory()->staff()->create();
    $clientUser = User::factory()->forClient(null)->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->isInternal())->toBeTrue()
        ->and($staff->isStaff())->toBeTrue()
        ->and($staff->isInternal())->toBeTrue()
        ->and($clientUser->isClient())->toBeTrue()
        ->and($clientUser->isInternal())->toBeFalse();
});

test('a client user can be linked to a client account', function () {
    $client = Client::create([
        'name' => 'ABC Company',
        'currency' => 'USD',
        'status' => 'active',
    ]);

    $user = User::factory()->forClient($client)->create();

    expect($user->client_id)->toBe($client->id)
        ->and($user->client->name)->toBe('ABC Company')
        ->and($client->users()->count())->toBe(1);
});

test('initial currencies are seeded', function () {
    $this->seed(CurrencySeeder::class);

    expect(Currency::pluck('code')->sort()->values()->all())
        ->toBe(['AED', 'EGP', 'EUR', 'GBP', 'SAR', 'USD']);
});

test('default payment methods setting exists', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get(Setting::PAYMENT_METHODS))
        ->toBe(['Money Transfer', 'Handed', 'Check']);
});
