<?php

namespace App\Http\Controllers\Portal;

use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends PortalController
{
    public function index(Request $request): Response
    {
        $client = $this->client($request);

        return Inertia::render('portal/payments/index', [
            'payments' => $client->payments()
                ->active()
                ->with('project:id,name')
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function show(Request $request, Payment $payment): Response
    {
        $client = $this->client($request);

        // Client users can only access their own records (PRD §80.9).
        abort_unless($payment->client_id === $client->id, 404);

        return Inertia::render('portal/payments/show', [
            'payment' => $payment->load('project:id,name'),
            'comments' => $payment->comments()
                ->with('user:id,name')
                ->where('is_internal', false)
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }
}
