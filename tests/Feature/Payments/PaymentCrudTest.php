<?php

use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\ClientAccountService;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Date;

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
        'subtotal' => '5000',
        'currency' => 'USD',
        'status' => Project::STATUS_ACTIVE,
    ]);
});

function testClient(): Client
{
    return Client::where('name', 'ABC Company')->firstOrFail();
}

function testProject(): Project
{
    return Project::where('name', 'Website Design')->firstOrFail();
}

function paymentPayload(array $overrides = []): array
{
    return array_merge([
        'client_id' => testClient()->id,
        'project_id' => testProject()->id,
        'amount' => '2000',
        'currency' => 'USD',
        'payment_date' => Date::today()->toDateString(),
        'method' => 'Money Transfer',
        'received_from' => 'John Doe',
        'received_by' => 'Jane Smith',
        'note' => 'First installment',
    ], $overrides);
}

test('client-role users cannot access payment management', function () {
    $clientUser = User::factory()->forClient(null)->create();

    $this->actingAs($clientUser)->get(route('payments.index'))->assertRedirect(route('portal.dashboard'));
    $this->actingAs($clientUser)->post(route('payments.store'), paymentPayload())->assertRedirect(route('portal.dashboard'));
});

test('an administrator can record a payment assigned to a project', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('payments.store'), paymentPayload())
        ->assertRedirect();

    $payment = Payment::firstOrFail();

    expect($payment->amount)->toBe(2000)
        ->and($payment->project_id)->toBe(testProject()->id)
        ->and($payment->status)->toBe(Payment::STATUS_ACTIVE);

    $this->actingAs($admin)->get(route('payments.show', $payment))->assertOk();
    $this->actingAs($admin)->get(route('payments.edit', $payment))->assertOk();
});

test('a payment can be recorded without a project (account payment)', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('payments.store'), paymentPayload(['project_id' => null]))
        ->assertRedirect();

    expect(Payment::firstOrFail()->project_id)->toBeNull();
});

test('the form "none" project value is normalized to an account payment', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('payments.store'), paymentPayload(['project_id' => 'none']))
        ->assertRedirect();

    expect(Payment::firstOrFail()->project_id)->toBeNull();
});

test('a payment cannot be assigned to another client\'s project', function () {
    $admin = User::factory()->create();
    $otherClient = Client::create(['name' => 'XYZ Ltd', 'currency' => 'USD', 'status' => 'active']);
    $otherProject = Project::create([
        'client_id' => $otherClient->id,
        'name' => 'Other Work',
        'subtotal' => '1000',
        'currency' => 'USD',
        'status' => Project::STATUS_ACTIVE,
    ]);

    $this->actingAs($admin)
        ->post(route('payments.store'), paymentPayload(['project_id' => $otherProject->id]))
        ->assertInvalid('project_id');

    expect(Payment::count())->toBe(0);
});

test('a payment currency must match the assigned project currency', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('payments.store'), paymentPayload(['currency' => 'EGP']))
        ->assertInvalid('currency');

    expect(Payment::count())->toBe(0);
});

test('payment method must be one of the configured methods', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('payments.store'), paymentPayload(['method' => 'Barter']))
        ->assertInvalid('method');
});

test('assigned payments reduce the project balance', function () {
    Payment::create(paymentPayload(['amount' => '2000']));
    Payment::create(paymentPayload(['amount' => '1000']));

    $balance = app(ClientAccountService::class)->projectBalance(testProject());

    expect($balance)->toBe(2000);
});

test('unassigned payments reduce overall outstanding but not project balances', function () {
    Payment::create(paymentPayload(['amount' => '2000']));
    Payment::create(paymentPayload(['amount' => '3000', 'project_id' => null]));

    $service = app(ClientAccountService::class);

    expect($service->projectBalance(testProject()))->toBe(3000)
        ->and($service->summary(testClient())['currencies']['USD']['outstanding'])->toBe(0)
        ->and($service->summary(testClient())['currencies']['USD']['payments_total'])->toBe(5000);
});

test('voided payments are excluded from all balances', function () {
    Payment::create(paymentPayload(['amount' => '2000']));
    Payment::create(paymentPayload(['amount' => '3000', 'status' => Payment::STATUS_VOID]));

    $service = app(ClientAccountService::class);

    expect($service->projectBalance(testProject()))->toBe(3000)
        ->and($service->summary(testClient())['currencies']['USD']['payments_total'])->toBe(2000)
        ->and($service->summary(testClient())['currencies']['USD']['outstanding'])->toBe(3000);
});

test('overpayment becomes client credit, never a negative outstanding', function () {
    Payment::create(paymentPayload(['amount' => '6000']));

    $summary = app(ClientAccountService::class)->summary(testClient())['currencies']['USD'];

    expect($summary['outstanding'])->toBe(0)
        ->and($summary['credit'])->toBe(1000);
});

test('an administrator can void a payment but not delete it', function () {
    $admin = User::factory()->create();
    $payment = Payment::create(paymentPayload());

    $this->actingAs($admin)
        ->patch(route('payments.void', $payment))
        ->assertRedirect(route('payments.show', $payment));

    expect($payment->refresh()->status)->toBe(Payment::STATUS_VOID);

    // Voiding twice is not allowed by policy.
    $this->actingAs($admin)
        ->patch(route('payments.void', $payment))
        ->assertForbidden();
});

test('payments appear in the global history list', function () {
    $admin = User::factory()->create();
    Payment::create(paymentPayload());

    $this->actingAs($admin)->get(route('payments.index'))->assertOk();
});
