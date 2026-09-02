<?php

namespace App\Http\Controllers\Portal;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends PortalController
{
    public function index(Request $request): Response
    {
        $client = $this->client($request);

        return Inertia::render('portal/projects/index', [
            'projects' => $client->projects()
                ->orderByDesc('project_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function show(Request $request, Project $project): Response
    {
        $client = $this->client($request);

        // Client users can only access their own records (PRD §80.9).
        abort_unless($project->client_id === $client->id, 404);

        return Inertia::render('portal/projects/show', [
            'project' => $project,
            'balance' => $this->accounts()->projectBalance($project),
            'payments' => $project->payments()->active()->orderByDesc('payment_date')->get(),
            'comments' => $project->comments()
                ->with('user:id,name')
                ->where('is_internal', false)
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }
}
