<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Services\ClientAccountService;
use Database\Seeders\CurrencySeeder;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
    createClientWithCurrency('ABC Company', 'USD');
});

function projectPayload(array $overrides = []): array
{
    $client = Client::where('name', 'ABC Company')->firstOrFail();

    return array_merge([
        'client_id' => $client->id,
        'name' => 'Website Design',
        'description' => 'Marketing site redesign',
        'amount' => '3500',
        'currency' => 'USD',
        'status' => Project::STATUS_ACTIVE,
        'link' => 'https://example.com/project',
    ], $overrides);
}

function createClientWithCurrency(string $name, string $currency): Client
{
    return Client::create([
        'name' => $name,
        'currency' => $currency,
        'status' => Client::STATUS_ACTIVE,
    ]);
}

test('client-role users cannot access project management', function () {
    $clientUser = User::factory()->forClient(null)->create();

    $this->actingAs($clientUser)->get(route('projects.index'))->assertRedirect(route('portal.dashboard'));
    $this->actingAs($clientUser)->get(route('projects.create'))->assertRedirect(route('portal.dashboard'));
    $this->actingAs($clientUser)->post(route('projects.store'), projectPayload())->assertRedirect(route('portal.dashboard'));
});

test('an administrator can create a project for a client', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin)
        ->post(route('projects.store'), projectPayload())
        ->assertRedirect();

    $project = Project::where('name', 'Website Design')->firstOrFail();

    expect($project->amount)->toBe(3500)
        ->and($project->currency)->toBe('USD')
        ->and($project->status)->toBe(Project::STATUS_ACTIVE);

    $this->actingAs($admin)->get(route('projects.show', $project))->assertOk();
    $this->actingAs($admin)->get(route('projects.edit', $project))->assertOk();
});

test('project creation validates amount, currency and link', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin)
        ->post(route('projects.store'), projectPayload([
            'amount' => '-5',
            'currency' => 'XXX',
            'link' => 'not-a-url',
        ]))
        ->assertInvalid(['amount', 'currency', 'link']);

    expect(Project::count())->toBe(0);
});

test('an administrator can update a project including cancelling it', function () {
    $admin = User::factory()->create();
    $client = Client::where('name', 'ABC Company')->firstOrFail();
    $project = Project::create(projectPayload(['client_id' => $client->id]));

    $this->actingAs($admin)
        ->put(route('projects.update', $project), projectPayload([
            'client_id' => $client->id,
            'name' => 'Website Redesign',
            'status' => Project::STATUS_CANCELLED,
        ]))
        ->assertRedirect(route('projects.show', $project));

    expect($project->refresh()->name)->toBe('Website Redesign')
        ->and($project->status)->toBe(Project::STATUS_CANCELLED);
});

test('cancelled projects are excluded from the outstanding total but preserved', function () {
    $client = Client::where('name', 'ABC Company')->firstOrFail();
    Project::create(projectPayload(['client_id' => $client->id, 'amount' => '3500']));
    Project::create(projectPayload(['client_id' => $client->id, 'amount' => '1500', 'name' => 'Logo']));
    Project::create(projectPayload(['client_id' => $client->id, 'amount' => '9999', 'name' => 'Cancelled Work', 'status' => Project::STATUS_CANCELLED]));

    $summary = app(ClientAccountService::class)->summary($client);

    expect($summary['currencies']['USD']['projects_total'])->toBe(5000)
        ->and($summary['currencies']['USD']['outstanding'])->toBe(5000)
        ->and($summary['currencies']['USD']['credit'])->toBe(0)
        ->and($client->projects()->count())->toBe(3); // cancelled record preserved
});

test('overpayment produces credit, never a negative outstanding', function () {
    // Simulated by the service directly: projects 1000, payments 1250.
    $client = Client::where('name', 'ABC Company')->firstOrFail();

    $service = app(ClientAccountService::class);
    $line = $service->summary($client)['currencies']['USD'];

    // With no payments yet, outstanding equals projects total.
    expect($line['outstanding'])->toBe(0);

    // Exercise the credit branch of the calculation directly.
    $method = new ReflectionMethod($service, 'buildLine');
    $creditLine = $method->invoke($service, 1000, 1250);

    expect($creditLine['net'])->toBe(-250)
        ->and($creditLine['outstanding'])->toBe(0)
        ->and($creditLine['credit'])->toBe(250);
});

test('multi-currency accounts are reported per currency and never combined', function () {
    $client = Client::where('name', 'ABC Company')->firstOrFail();
    Project::create(projectPayload(['client_id' => $client->id, 'amount' => '1000']));
    Project::create(projectPayload(['client_id' => $client->id, 'amount' => '500', 'currency' => 'EGP', 'name' => 'Local Work']));

    $summary = app(ClientAccountService::class)->summary($client);

    expect($summary['has_multiple_currencies'])->toBeTrue()
        ->and($summary['currencies']['USD']['projects_total'])->toBe(1000)
        ->and($summary['currencies']['EGP']['projects_total'])->toBe(500)
        ->and(count($summary['currencies']))->toBe(2);
});

test('a client with no projects reports zero in its default currency', function () {
    $client = createClientWithCurrency('ABC Company', 'SAR');

    $summary = app(ClientAccountService::class)->summary($client);

    expect($summary['currencies']['SAR']['projects_total'])->toBe(0)
        ->and($summary['currencies']['SAR']['outstanding'])->toBe(0)
        ->and($summary['has_multiple_currencies'])->toBeFalse();
});
