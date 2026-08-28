<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommentController extends Controller
{
    /**
     * Attach a comment to a project or payment (PRD §24).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'commentable_type' => ['required', 'in:project,payment'],
            'commentable_id' => ['required', 'integer', 'min:1'],
            'body' => ['required', 'string', 'max:2000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $type = (string) $validated['commentable_type'];
        $id = (int) $validated['commentable_id'];

        $commentable = $type === 'project'
            ? Project::query()->findOrFail($id)
            : Payment::query()->findOrFail($id);

        $this->authorize('create', [Comment::class, $commentable]);

        $comment = $commentable->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            // Client users can never flag comments as internal (PRD §80.8).
            'is_internal' => $request->user()->isInternal() && $request->boolean('is_internal'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment added.')]);

        return back();
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment deleted.')]);

        return back();
    }
}
