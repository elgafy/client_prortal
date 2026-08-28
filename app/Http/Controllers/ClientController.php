<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use App\Models\Currency;
use App\Models\User;
use App\Services\ClientAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientAccountService $accounts,
    ) {}

    /**
     * Client list with search across name, company, email and mobile (PRD §48).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::query()
            ->when($request->string('search')->trim(), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => ['search' => $request->string('search')->trim()->toString()],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('clients/create', [
            'currencies' => Currency::query()->orderBy('code')->pluck('code'),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $client = Client::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client created.')]);

        return to_route('clients.show', $client);
    }

    public function show(Client $client): Response
    {
        $this->authorize('view', $client);

        return Inertia::render('clients/show', [
            'client' => $client,
            'projects' => $client->projects()->orderByDesc('created_at')->get(),
            'payments' => $client->payments()->with('project:id,name')->orderByDesc('payment_date')->orderByDesc('id')->get(),
            'summary' => $this->accounts->summary($client),
        ]);
    }

    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('clients/edit', [
            'client' => $client,
            'currencies' => Currency::query()->orderBy('code')->pluck('code'),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client updated.')]);

        return to_route('clients.show', $client);
    }

    /**
     * Invite the client to the portal: create (or reuse) a client-role user
     * and email a secure set-password link (PRD §19).
     */
    public function invite(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('invite', $client);

        if (! $client->email) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Add an email address to this client before sending a portal invitation.'),
            ]);

            return back();
        }

        $user = User::query()->firstOrNew(['email' => $client->email]);
        $user->name = $client->name;
        $user->role = User::ROLE_CLIENT;
        $user->client_id = $client->id;
        $user->password ??= Hash::make(Str::random(32)); // set for real via the invitation link
        $user->save();

        Password::sendResetLink(['email' => $user->email]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Portal invitation sent to :email.', ['email' => $user->email]),
        ]);

        return back();
    }

    /**
     * Archive/restore a client. Clients are never deleted — archiving hides them
     * from active lists while preserving history (PRD §7, §64).
     */
    public function archive(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('archive', $client);

        $validated = $request->validate([
            'status' => ['required', 'in:'.Client::STATUS_ACTIVE.','.Client::STATUS_ARCHIVED],
        ]);

        $client->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $client->status === Client::STATUS_ARCHIVED ? __('Client archived.') : __('Client restored.'),
        ]);

        return to_route('clients.show', $client);
    }
}
