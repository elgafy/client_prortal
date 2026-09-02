<?php

use App\Models\Client;
use App\Models\Payment;
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
        'subtotal' => '3500',
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
        ->and($project->subtotal)->toBe(3500)
        ->and($project->discount_total)->toBe(0)
        ->and($project->currency)->toBe('USD')
        ->and($project->status)->toBe(Project::STATUS_ACTIVE);

    $this->actingAs($admin)->get(route('projects.show', $project))->assertOk();
    $this->actingAs($admin)->get(route('projects.edit', $project))->assertOk();
});

test('project creation validates amount, currency and link', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin)
        ->post(route('projects.store'), projectPayload([
            'subtotal' => '-5',
            'currency' => 'XXX',
            'link' => 'not-a-url',
        ]))
        ->assertInvalid(['subtotal', 'currency', 'link']);

    expect(Project::count())->toBe(0);
});

test('discounts and deductions reduce the final amount, never the subtotal', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('projects.store'), projectPayload([
            'subtotal' => '5000',
            'discounts' => [
                [
                    'title' => 'Early payment',
                    'type' => 'discount',
                    'mode' => 'amount',
                    'amount' => '500',
                    'description' => 'Paid up front',
                ],
                [
                    'title' => 'Penalty',
                    'type' => 'deduction',
                    'mode' => 'percentage',
                    'percentage' => '10',
                ],
            ],
        ]))
        ->assertRedirect();

    $project = Project::where('name', 'Website Design')->firstOrFail();

    expect($project->subtotal)->toBe(5000)
        ->and($project->discount_total)->toBe(1000) // 500 + 10% of 5000
        ->and($project->amount)->toBe(4000)
        ->and($project->discounts()->count())->toBe(2)
        ->and($project->discounts()->first()->title)->toBe('Early payment');
});

test('percentage discounts are rounded to whole currency units', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('projects.store'), projectPayload([
            'subtotal' => '333',
            'discounts' => [
                [
                    'title' => 'Ten percent',
                    'type' => 'discount',
                    'mode' => 'percentage',
                    'percentage' => '10',
                ],
            ],
        ]))
        ->assertRedirect();

    $project = Project::where('name', 'Website Design')->firstOrFail();

    // 10% of 333 = 33.3 → rounds to 33 whole currency units.
    expect($project->discount_total)->toBe(33)
        ->and($project->amount)->toBe(300);
});

test('combined discounts cannot exceed the subtotal', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('projects.store'), projectPayload([
            'subtotal' => '1000',
            'discounts' => [
                [
                    'title' => 'Too much',
                    'type' => 'discount',
                    'mode' => 'amount',
                    'amount' => '1001',
                ],
            ],
        ]))
        ->assertInvalid(['discounts']);

    expect(Project::count())->toBe(0);
});

test('discount rows are validated for structure', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('projects.store'), projectPayload([
            'discounts' => [
                ['type' => 'bogus', 'mode' => 'amount'], // no title, bad type, no amount
                [
                    'title' => 'Bad percentage',
                    'type' => 'discount',
                    'mode' => 'percentage',
                    'percentage' => '150',
                ],
            ],
        ]))
        ->assertInvalid([
            'discounts.0.title',
            'discounts.0.type',
            'discounts.0.amount',
            'discounts.1.percentage',
        ]);

    expect(Project::count())->toBe(0);
});

test('updating a project replaces its discounts and recalculates', function () {
    $admin = User::factory()->create();
    $client = Client::where('name', 'ABC Company')->firstOrFail();
    $project = Project::create(projectPayload(['client_id' => $client->id, 'subtotal' => '5000']));
    $project->discounts()->create([
        'title' => 'Old discount',
        'type' => 'discount',
        'mode' => 'amount',
        'amount' => 1000,
    ]);
    $project->recalculateAmount();

    $this->actingAs($admin)
        ->put(route('projects.update', $project), projectPayload([
            'client_id' => $client->id,
            'subtotal' => '5000',
            'discounts' => [
                [
                    'title' => 'New discount',
                    'type' => 'deduction',
                    'mode' => 'amount',
                    'amount' => '250',
                ],
            ],
        ]))
        ->assertRedirect(route('projects.show', $project));

    expect($project->refresh()->discounts()->count())->toBe(1)
        ->and($project->discounts()->first()->title)->toBe('New discount')
        ->and($project->discount_total)->toBe(250)
        ->and($project->amount)->toBe(4750);
});

test('project balances are calculated from the discounted final amount', function () {
    $client = Client::where('name', 'ABC Company')->firstOrFail();
    $project = Project::create(projectPayload(['client_id' => $client->id, 'subtotal' => '1000']));
    $project->discounts()->create([
        'title' => 'Discount',
        'type' => 'discount',
        'mode' => 'amount',
        'amount' => 200,
    ]);
    $project->recalculateAmount();

    $payment = Payment::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'amount' => 300,
        'currency' => 'USD',
        'method' => 'Money Transfer',
        'payment_date' => '2026-01-15',
        'status' => Payment::STATUS_ACTIVE,
    ]);

    $balance = app(ClientAccountService::class)->projectBalance($project);

    expect($project->amount)->toBe(800)
        ->and($balance)->toBe(500);

    $payment->update(['status' => Payment::STATUS_VOID]);

    expect(app(ClientAccountService::class)->projectBalance($project))->toBe(800);
});

test('a client with no projects reports zero in its default currency', function () {
    $client = createClientWithCurrency('ABC Company', 'SAR');

    $summary = app(ClientAccountService::class)->summary($client);

    expect($summary['currencies']['SAR']['projects_total'])->toBe(0)
        ->and($summary['currencies']['SAR']['outstanding'])->toBe(0)
        ->and($summary['has_multiple_currencies'])->toBeFalse();
});
