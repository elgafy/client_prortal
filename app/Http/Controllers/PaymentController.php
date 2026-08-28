<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payments\StorePaymentRequest;
use App\Http\Requests\Payments\UpdatePaymentRequest;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Global payment history (PRD §23–24): date, client, amount, currency,
     * method, project, status.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['client:id,name', 'project:id,name'])
            ->when($request->string('search')->trim(), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('method', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('project', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('payments/index', [
            'payments' => $payments,
            'filters' => ['search' => $request->string('search')->trim()->toString()],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Payment::class);

        return Inertia::render('payments/create', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'currency']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'client_id', 'currency']),
            'methods' => (array) Setting::get(Setting::PAYMENT_METHODS, []),
            'currencies' => Currency::query()->orderBy('code')->pluck('code'),
            'selectedClientId' => $request->integer('client') ?: null,
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $payment = Payment::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment recorded.')]);

        return to_route('payments.show', $payment);
    }

    public function show(Payment $payment): Response
    {
        $this->authorize('view', $payment);

        return Inertia::render('payments/show', [
            'payment' => $payment->load(['client:id,name', 'project:id,name']),
            'comments' => $payment->comments()->with('user:id,name')->orderByDesc('created_at')->get(),
        ]);
    }

    public function edit(Payment $payment): Response
    {
        $this->authorize('update', $payment);

        return Inertia::render('payments/edit', [
            'payment' => $payment,
            'projects' => Project::query()
                ->where('client_id', $payment->client_id)
                ->orderBy('name')
                ->get(['id', 'name', 'client_id', 'currency']),
            'methods' => (array) Setting::get(Setting::PAYMENT_METHODS, []),
        ]);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $payment->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment updated.')]);

        return to_route('payments.show', $payment);
    }

    /**
     * Void a payment. Payments are never deleted — voiding preserves
     * financial history while excluding the amount from balances (PRD §51–52).
     */
    public function void(Payment $payment): RedirectResponse
    {
        $this->authorize('void', $payment);

        $payment->update(['status' => Payment::STATUS_VOID]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment voided.')]);

        return to_route('payments.show', $payment);
    }
}
