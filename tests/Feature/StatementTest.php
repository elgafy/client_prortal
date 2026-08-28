<?php

use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\SettingSeeder;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
    $this->seed(SettingSeeder::class);

    $client = Client::create([
        'name' => 'ABC Company',
        'currency' => 'USD',
        'status' => Client::STATUS_ACTIVE,
    ]);

    Project::create([
        'client_id' => $client->id,
        'name' => 'Website Design',
        'amount' => 5000,
        'currency' => 'USD',
        'status' => Project::STATUS_ACTIVE,
    ]);

    Payment::create([
        'client_id' => $client->id,
        'project_id' => Project::where('name', 'Website Design')->firstOrFail()->id,
        'amount' => 2000,
        'currency' => 'USD',
        'payment_date' => '2026-01-10',
        'method' => 'Money Transfer',
        'status' => Payment::STATUS_ACTIVE,
    ]);

    Payment::create([
        'client_id' => $client->id,
        'amount' => 1000,
        'currency' => 'USD',
        'payment_date' => '2026-02-20',
        'method' => 'Handed',
        'status' => Payment::STATUS_VOID,
    ]);
});

function statementClient(): Client
{
    return Client::where('name', 'ABC Company')->firstOrFail();
}

test('administrators can view a client statement', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('clients.statement', statementClient()))
        ->assertOk();
});

test('the statement summary always uses complete totals regardless of payment filter', function () {
    $admin = User::factory()->create();

    // Filter excludes the January payment entirely — but totals must not change (PRD §32).
    $response = $this->actingAs($admin)
        ->get(route('clients.statement', [
            'client' => statementClient()->id,
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ]));

    $response->assertOk();

    $props = $response->viewData('page')['props'];
    expect($props['statement']['summary']['currencies']['USD']['payments_total'])->toBe(2000)
        ->and($props['statement']['summary']['currencies']['USD']['outstanding'])->toBe(3000)
        ->and($props['statement']['payments'])->toHaveCount(0); // filtered history
});

test('administrators can download the statement as PDF', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('clients.statement.pdf', statementClient()));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf');
});

test('administrators can download the statement as Excel', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('clients.statement.excel', statementClient()));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
});

test('client users can view and export their own statement', function () {
    $user = User::factory()->forClient(statementClient())->create();

    $this->actingAs($user)->get(route('portal.statement'))->assertOk();
    $this->actingAs($user)->get(route('portal.statement.pdf'))->assertOk();
    $this->actingAs($user)->get(route('portal.statement.excel'))->assertOk();
});

test('internal users are redirected away from the portal statement', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('portal.statement'))
        ->assertRedirect(route('dashboard'));
});
