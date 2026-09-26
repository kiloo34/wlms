import React, { useState } from 'react';
import { format } from 'date-fns';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useIssueComments } from '../hooks/useIssueComments';

interface IssueCommentSectionProps {
    issueId: string;
}

export const IssueCommentSection: React.FC<IssueCommentSectionProps> = ({ issueId }) => {
    const { comments, isLoading, addComment, isAdding } = useIssueComments(issueId);
    const [newComment, setNewComment] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newComment.trim()) return;

        await addComment({ body: newComment });
        setNewComment('');
    };

    return (
        <div className="space-y-6 mt-4">
            <div className="space-y-4">
                <h3 className="text-sm font-medium">Comments</h3>
                {isLoading ? (
                    <div className="text-center text-sm text-muted-foreground py-4">Loading comments...</div>
                ) : comments.length === 0 ? (
                    <div className="text-center text-sm text-muted-foreground py-4">No comments yet.</div>
                ) : (
                    <div className="space-y-4">
                        {comments.map((comment) => (
                            <div key={comment.id} className="flex gap-4 p-4 border rounded-lg bg-background">
                                <div className="flex-1 space-y-1">
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm font-medium">{comment.author?.name || 'Unknown User'}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {format(new Date(comment.created_at), "MMM d, yyyy 'at' h:mm a")}
                                        </p>
                                    </div>
                                    <p className="text-sm mt-2 text-foreground whitespace-pre-wrap">
                                        {comment.body}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <form onSubmit={handleSubmit} className="space-y-4 pt-4 border-t">
                <Textarea
                    placeholder="Add a comment..."
                    value={newComment}
                    onChange={(e) => setNewComment(e.target.value)}
                    rows={3}
                    disabled={isAdding}
                />
                <div className="flex justify-end">
                    <Button type="submit" disabled={isAdding || !newComment.trim()}>
                        {isAdding ? 'Posting...' : 'Post Comment'}
                    </Button>
                </div>
            </form>
        </div>
    );
};

