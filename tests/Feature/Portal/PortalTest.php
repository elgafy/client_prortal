<?php

use App\Models\Client;
use App\Models\Comment;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
    $this->seed(SettingSeeder::class);

    $client = Client::create([
        'name' => 'ABC Company',
        'email' => 'abc@example.com',
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
});

function portalClient(): Client
{
    return Client::where('name', 'ABC Company')->firstOrFail();
}

function portalProject(): Project
{
    return Project::where('name', 'Website Design')->firstOrFail();
}

function portalUser(): User
{
    $client = Client::where('name', 'ABC Company')->firstOrFail();

    return User::factory()->forClient($client)->create();
}

test('an administrator can send a portal invitation', function () {
    Notification::fake();

    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('clients.invite', portalClient()))
        ->assertRedirect();

    $user = User::where('email', 'abc@example.com')->firstOrFail();

    expect($user->role)->toBe(User::ROLE_CLIENT)
        ->and($user->client_id)->toBe(portalClient()->id);
});

test('invitation requires the client to have an email address', function () {
    $admin = User::factory()->create();
    portalClient()->update(['email' => null]);

    $this->actingAs($admin)
        ->post(route('clients.invite', portalClient()))
        ->assertRedirect();

    expect(User::where('role', User::ROLE_CLIENT)->count())->toBe(0);
});

test('client-role users are redirected from internal routes to the portal', function () {
    $user = portalUser();

    $this->actingAs($user)->get(route('clients.index'))->assertRedirect(route('portal.dashboard'));
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('portal.dashboard'));
});

test('internal users are redirected away from portal routes', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->get(route('portal.dashboard'))->assertRedirect(route('dashboard'));
});

test('guests are redirected to login from portal routes', function () {
    $this->get(route('portal.dashboard'))->assertRedirect(route('login'));
});

test('a client user can view the portal pages', function () {
    $user = portalUser();

    $this->actingAs($user)->get(route('portal.dashboard'))->assertOk();
    $this->actingAs($user)->get(route('portal.projects.index'))->assertOk();
    $this->actingAs($user)->get(route('portal.projects.show', portalProject()))->assertOk();
    $this->actingAs($user)->get(route('portal.payments.index'))->assertOk();
    $this->actingAs($user)->get(route('portal.balance'))->assertOk();
});

test('a client user cannot view another client\'s project', function () {
    $user = portalUser();

    $otherClient = Client::create(['name' => 'XYZ Ltd', 'currency' => 'USD', 'status' => 'active']);
    $otherProject = Project::create([
        'client_id' => $otherClient->id,
        'name' => 'Other Work',
        'amount' => 1000,
        'currency' => 'USD',
        'status' => Project::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)
        ->get(route('portal.projects.show', $otherProject))
        ->assertNotFound();
});

test('client users cannot write comments on other clients\' records', function () {
    $user = portalUser();

    $otherClient = Client::create(['name' => 'XYZ Ltd', 'currency' => 'USD', 'status' => 'active']);
    $otherProject = Project::create([
        'client_id' => $otherClient->id,
        'name' => 'Other Work',
        'amount' => 1000,
        'currency' => 'USD',
        'status' => Project::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)
        ->post(route('comments.store'), [
            'commentable_type' => 'project',
            'commentable_id' => $otherProject->id,
            'body' => 'Hello',
        ])
        ->assertForbidden();

    expect(Comment::count())->toBe(0);
});

test('client users can comment on their own project but never as internal', function () {
    $user = portalUser();

    $this->actingAs($user)
        ->post(route('comments.store'), [
            'commentable_type' => 'project',
            'commentable_id' => portalProject()->id,
            'body' => 'When will this be finished?',
            'is_internal' => true, // must be ignored for client users
        ])
        ->assertRedirect();

    $comment = Comment::firstOrFail();

    expect($comment->body)->toBe('When will this be finished?')
        ->and($comment->is_internal)->toBeFalse()
        ->and($comment->user_id)->toBe($user->id);
});

test('client users can comment on their own payment', function () {
    $user = portalUser();

    $payment = Payment::create([
        'client_id' => portalClient()->id,
        'project_id' => portalProject()->id,
        'amount' => 2000,
        'currency' => 'USD',
        'payment_date' => now()->toDateString(),
        'method' => 'Money Transfer',
        'status' => Payment::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)
        ->post(route('comments.store'), [
            'commentable_type' => 'payment',
            'commentable_id' => $payment->id,
            'body' => 'Thanks, received!',
        ])
        ->assertRedirect();

    expect($payment->comments()->count())->toBe(1);
});

test('staff can write internal and client-visible comments', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('comments.store'), [
            'commentable_type' => 'project',
            'commentable_id' => portalProject()->id,
            'body' => 'Client requested the final version.',
        ])
        ->assertRedirect();

    $this->actingAs($staff)
        ->post(route('comments.store'), [
            'commentable_type' => 'project',
            'commentable_id' => portalProject()->id,
            'body' => 'Chase payment next week.',
            'is_internal' => true,
        ])
        ->assertRedirect();

    expect(portalProject()->comments()->count())->toBe(2);
});

test('the portal only shows client-visible comments', function () {
    $staff = User::factory()->staff()->create();

    Comment::create([
        'user_id' => $staff->id,
        'commentable_type' => Project::class,
        'commentable_id' => portalProject()->id,
        'body' => 'Visible to client',
        'is_internal' => false,
    ]);
    Comment::create([
        'user_id' => $staff->id,
        'commentable_type' => Project::class,
        'commentable_id' => portalProject()->id,
        'body' => 'Internal note',
        'is_internal' => true,
    ]);

    $response = $this->actingAs(portalUser())
        ->get(route('portal.projects.show', portalProject()));

    $response->assertOk();

    $comments = $response->viewData('page')['props']['comments'];
    expect(count($comments))->toBe(1)
        ->and($comments[0]['body'])->toBe('Visible to client');
});

test('the portal shows only active payments', function () {
    $user = portalUser();

    Payment::create([
        'client_id' => portalClient()->id,
        'project_id' => portalProject()->id,
        'amount' => 2000,
        'currency' => 'USD',
        'payment_date' => now()->toDateString(),
        'method' => 'Money Transfer',
        'status' => Payment::STATUS_ACTIVE,
    ]);
    Payment::create([
        'client_id' => portalClient()->id,
        'project_id' => portalProject()->id,
        'amount' => 3000,
        'currency' => 'USD',
        'payment_date' => now()->toDateString(),
        'method' => 'Check',
        'status' => Payment::STATUS_VOID,
    ]);

    $response = $this->actingAs($user)
        ->get(route('portal.payments.index'));

    $response->assertOk();

    $payments = $response->viewData('page')['props']['payments'];
    expect(count($payments))->toBe(1)
        ->and($payments[0]['amount'])->toBe(2000);
});

test('a client user can view their own payment detail', function () {
    $user = portalUser();

    $payment = Payment::create([
        'client_id' => portalClient()->id,
        'project_id' => portalProject()->id,
        'amount' => 2000,
        'currency' => 'USD',
        'payment_date' => now()->toDateString(),
        'method' => 'Money Transfer',
        'status' => Payment::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)
        ->get(route('portal.payments.show', $payment))
        ->assertOk();
});

test('a client user cannot view another client\'s payment detail', function () {
    $user = portalUser();

    $otherClient = Client::create(['name' => 'XYZ Ltd', 'currency' => 'USD', 'status' => 'active']);
    $otherPayment = Payment::create([
        'client_id' => $otherClient->id,
        'amount' => 1000,
        'currency' => 'USD',
        'payment_date' => now()->toDateString(),
        'method' => 'Check',
        'status' => Payment::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)
        ->get(route('portal.payments.show', $otherPayment))
        ->assertNotFound();
});

test('the admin dashboard shows account-wide totals', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();

    $summary = $response->viewData('page')['props']['summary'];
    expect($summary['currencies']['USD']['projects_total'])->toBe(5000)
        ->and($summary['currencies']['USD']['outstanding'])->toBe(5000);
});
