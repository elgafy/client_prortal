<?php

namespace App\Http\Controllers;

use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Project;
use App\Services\ClientAccountService;
use App\Services\ProjectDiscountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ClientAccountService $accounts,
        private readonly ProjectDiscountService $discounts,
    ) {}

    /**
     * Global project list with search across project and client names (PRD §65).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->with('client:id,name')
            ->when($request->string('search')->trim(), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('projects.name', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('project_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('projects/index', [
            'projects' => $projects,
            'filters' => ['search' => $request->string('search')->trim()->toString()],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('projects/create', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'currency']),
            'currencies' => Currency::query()->orderBy('code')->pluck('code'),
            'selectedClientId' => $request->integer('client') ?: null,
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = DB::transaction(function () use ($request): Project {
            $project = Project::create($this->projectAttributes($request));
            $this->discounts->sync($project, (array) $request->input('discounts', []));

            return $project;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', $project);
    }

    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/show', [
            'project' => $project->load(['client:id,name', 'discounts.project']),
            'balance' => $this->accounts->projectBalance($project),
            'payments' => $project->payments()->active()->orderByDesc('payment_date')->get(),
            'comments' => $project->comments()->with('user:id,name')->orderByDesc('created_at')->get(),
        ]);
    }

    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('projects/edit', [
            'project' => $project->load('discounts.project'),
            'currencies' => Currency::query()->orderBy('code')->pluck('code'),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        DB::transaction(function () use ($request, $project): void {
            $project->update($this->projectAttributes($request));
            $this->discounts->sync($project, (array) $request->input('discounts', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Delete a project. Assigned payments survive as unassigned account
     * payments (payments are never deleted, PRD §51); comments are removed.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        DB::transaction(function () use ($project): void {
            $project->comments()->delete();
            $project->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted.')]);

        return to_route('projects.index');
    }

    /**
     * The validated project fields — discounts are handled separately and
     * `amount` is always derived, never accepted from the client.
     *
     * @return array<string, mixed>
     */
    private function projectAttributes(StoreProjectRequest|UpdateProjectRequest $request): array
    {
        return collect($request->validated())
            ->except('discounts')
            ->all();
    }
}
