<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientAccountService;
use Illuminate\Http\Request;

/**
 * Client portal controllers serve read-only data scoped to the
 * authenticated user's own client account (PRD §19, §80.9).
 */
abstract class PortalController extends Controller
{
    public function __construct(
        private readonly ClientAccountService $accounts,
    ) {}

    /**
     * The client account owned by the authenticated portal user.
     */
    protected function client(Request $request): Client
    {
        /** @var Client $client */
        $client = $request->user()->client()->firstOrFail();

        return $client;
    }

    protected function accounts(): ClientAccountService
    {
        return $this->accounts;
    }
}
