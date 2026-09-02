import { Form, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import CommentController from '@/actions/App/Http/Controllers/CommentController';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';
import type { Comment } from '@/types';

type Props = {
    comments: Comment[];
    commentableType: 'project' | 'payment';
    commentableId: number;
};

export default function CommentThread({
    comments,
    commentableType,
    commentableId,
}: Props) {
    const [isInternal, setIsInternal] = useState(false);
    const { auth } = usePage().props;
    const isInternalUser = auth.user?.role !== 'client';

    return (
        <div className="space-y-4">
            <Form
                {...CommentController.store.form()}
                className="space-y-3 rounded-xl border p-4"
                onSuccess={() => setIsInternal(false)}
            >
                {({ processing }) => (
                    <>
                        <input
                            type="hidden"
                            name="commentable_type"
                            value={commentableType}
                        />
                        <input
                            type="hidden"
                            name="commentable_id"
                            value={commentableId}
                        />
                        <Textarea
                            name="body"
                            rows={2}
                            required
                            placeholder="Add a comment…"
                        />
                        <div className="flex items-center justify-between">
                            {isInternalUser ? (
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="is_internal"
                                        name="is_internal"
                                        value="1"
                                        checked={isInternal}
                                        onCheckedChange={(checked) =>
                                            setIsInternal(checked === true)
                                        }
                                    />
                                    <Label
                                        htmlFor="is_internal"
                                        className="text-sm"
                                    >
                                        Internal note (hidden from client)
                                    </Label>
                                </div>
                            ) : (
                                <span />
                            )}
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                Comment
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            {comments.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No comments yet.
                </p>
            ) : (
                <div className="space-y-3">
                    {comments.map((comment) => (
                        <div key={comment.id} className="rounded-xl border p-4">
                            <div className="flex items-start justify-between gap-4">
                                <p className="text-sm">{comment.body}</p>
                                <Form
                                    {...CommentController.destroy.form(
                                        comment.id,
                                    )}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="ghost"
                                            size="icon"
                                            disabled={processing}
                                            title="Delete comment"
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    )}
                                </Form>
                            </div>
                            <p className="mt-2 text-xs text-muted-foreground">
                                {comment.user?.name} ·{' '}
                                {formatDate(comment.created_at)}
                                {comment.is_internal && (
                                    <span className="ml-2 rounded-full bg-muted px-2 py-0.5">
                                        internal
                                    </span>
                                )}
                            </p>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
