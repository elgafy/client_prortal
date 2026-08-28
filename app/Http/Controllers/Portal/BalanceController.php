<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BalanceController extends PortalController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('portal/balance', [
            'summary' => $this->accounts()->summary($this->client($request)),
        ]);
    }
}
