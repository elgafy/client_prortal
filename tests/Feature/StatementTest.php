<?php

use App\Models\Client;
use App\Models\Comment;
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
        'subtotal' => 5000,
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

test('the Excel projects sheet shows dates and discount details without times', function () {
    $admin = User::factory()->create();
    $client = statementClient();

    $project = Project::create([
        'client_id' => $client->id,
        'name' => 'Discounted Work',
        'subtotal' => 1000,
        'currency' => 'USD',
        'project_date' => '2026-03-05',
        'status' => Project::STATUS_ACTIVE,
    ]);
    $project->discounts()->create([
        'title' => 'Early payment',
        'type' => 'discount',
        'mode' => 'amount',
        'amount' => 200,
    ]);
    $project->recalculateAmount();

    $payment = Payment::where('client_id', $client->id)->firstOrFail();
    Comment::create([
        'user_id' => $admin->id,
        'commentable_type' => Payment::class,
        'commentable_id' => $payment->id,
        'body' => 'Client asked about the invoice',
        'is_internal' => false,
    ]);
    Comment::create([
        'user_id' => $admin->id,
        'commentable_type' => Payment::class,
        'commentable_id' => $payment->id,
        'body' => 'Internal: verify bank transfer',
        'is_internal' => true,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('clients.statement.excel', $client));

    $response->assertOk();

    $zip = new ZipArchive;
    $zip->open($response->getFile()->getPathname());
    $shared = (string) $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();

    // Dates render as date-only strings, never datetimes.
    expect($shared)->toContain('Mar 5, 2026')
        ->and($shared)->toContain('Jan 10, 2026')
        ->and($shared)->not->toContain('00:00')
        // Discount cell: total plus each row's data in the same cell.
        ->and($shared)->toContain('-200 (Early payment: -200)')
        // Payments sheet: Received From (not Received By) + client-visible comments only.
        ->and($shared)->toContain('Received From')
        ->and($shared)->not->toContain('Received By')
        ->and($shared)->toContain('Client asked about the invoice')
        ->and($shared)->not->toContain('Verify bank transfer')
        ->and($shared)->not->toContain('Internal note');
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
