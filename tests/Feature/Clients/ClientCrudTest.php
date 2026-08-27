<?php

use App\Models\Client;
use App\Models\User;
use Database\Seeders\CurrencySeeder;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
});

function clientPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'ABC Company',
        'company_name' => 'ABC Group',
        'email' => 'abc@example.com',
        'mobile' => '+201234567890',
        'address' => 'Cairo, Egypt',
        'currency' => 'USD',
        'status' => Client::STATUS_ACTIVE,
    ], $overrides);
}

test('guests are redirected to login', function () {
    $this->get(route('clients.index'))->assertRedirect(route('login'));
});

test('administrators and staff can view the client list', function () {
    $admin = User::factory()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($admin)->get(route('clients.index'))->assertOk();
    $this->actingAs($staff)->get(route('clients.index'))->assertOk();
});

test('client-role users cannot access client management', function () {
    $clientUser = User::factory()->forClient(null)->create();

    $this->actingAs($clientUser)->get(route('clients.index'))->assertForbidden();
    $this->actingAs($clientUser)->get(route('clients.create'))->assertForbidden();
    $this->actingAs($clientUser)->post(route('clients.store'), clientPayload())->assertForbidden();
});

test('an administrator can create a client', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->get(route('clients.create'))->assertOk();

    $response = $this->actingAs($admin)
        ->post(route('clients.store'), clientPayload());

    $response->assertRedirect();

    $client = Client::where('name', 'ABC Company')->firstOrFail();

    expect($client->currency)->toBe('USD')
        ->and($client->status)->toBe(Client::STATUS_ACTIVE);

    $this->actingAs($admin)->get(route('clients.show', $client))->assertOk();
    $this->actingAs($admin)->get(route('clients.edit', $client))->assertOk();
});

test('client creation requires a name and a known currency', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('clients.store'), clientPayload(['name' => '', 'currency' => 'XXX']))
        ->assertInvalid(['name', 'currency']);

    expect(Client::count())->toBe(0);
});

test('an administrator can update a client', function () {
    $admin = User::factory()->create();
    $client = Client::create(clientPayload(['name' => 'Old Name']));

    $this->actingAs($admin)
        ->put(route('clients.update', $client), clientPayload(['name' => 'New Name', 'currency' => 'EGP']))
        ->assertRedirect(route('clients.show', $client));

    expect($client->refresh()->name)->toBe('New Name')
        ->and($client->currency)->toBe('EGP');
});

test('a client can be archived and restored without deleting it', function () {
    $admin = User::factory()->create();
    $client = Client::create(clientPayload());

    $this->actingAs($admin)
        ->patch(route('clients.archive', $client), ['status' => Client::STATUS_ARCHIVED])
        ->assertRedirect(route('clients.show', $client));

    expect($client->refresh()->status)->toBe(Client::STATUS_ARCHIVED);

    $this->actingAs($admin)
        ->patch(route('clients.archive', $client), ['status' => Client::STATUS_ACTIVE])
        ->assertRedirect(route('clients.show', $client));

    expect($client->refresh()->status)->toBe(Client::STATUS_ACTIVE);
});

test('the client list searches by name, company, email and mobile', function () {
    User::factory()->create();
    Client::create(clientPayload());
    Client::create(clientPayload([
        'name' => 'XYZ Ltd',
        'company_name' => 'XYZ Holdings',
        'email' => 'xyz@example.com',
        'mobile' => '+209876543210',
    ]));

    $find = function (string $search): array {
        $names = [];

        $this->actingAs(User::first())
            ->get(route('clients.index', ['search' => $search]))
            ->assertInertia(function ($page) use (&$names) {
                $page->component('clients/index');
                $names = collect($page->toArray()['props']['clients']['data'])->pluck('name')->all();
            });

        return $names;
    };

    expect($find('XYZ'))->toBe(['XYZ Ltd'])
        ->and($find('Holdings'))->toBe(['XYZ Ltd'])
        ->and($find('xyz@example.com'))->toBe(['XYZ Ltd'])
        ->and($find('+2098765432'))->toBe(['XYZ Ltd'])
        ->and($find('nomatch'))->toBe([]);
});
