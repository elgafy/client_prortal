<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use App\Models\Currency;
use App\Services\ClientAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
