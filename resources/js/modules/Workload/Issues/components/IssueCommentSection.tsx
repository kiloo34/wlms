import React, { useState } from 'react';
import { format } from 'date-fns';
import { MessageSquare, Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useIssueComments } from '../hooks/useIssueComments';
import { useTranslate } from '@/hooks/useTranslate';
import { ReferenceText } from '../../shared/ReferenceText';
import {
    MentionPerson,
    ReferenceTextarea,
} from '../../shared/ReferenceTextarea';

interface IssueCommentSectionProps {
    issueId: string;
    users?: MentionPerson[];
}

export const IssueCommentSection: React.FC<IssueCommentSectionProps> = ({
    issueId,
    users = [],
}) => {
    const { t } = useTranslate();
    const { comments, isLoading, addComment, isAdding } =
        useIssueComments(issueId);

    const [newComment, setNewComment] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newComment.trim()) return;

        await addComment({ body: newComment });
        setNewComment('');
    };

    return (
        <div className="mt-4 space-y-6">
            <div className="space-y-4">
                <h3 className="flex items-center gap-1.5 text-sm font-medium">
                    <MessageSquare className="text-muted-foreground h-4 w-4" />
                    <span>{t('Comments')}</span>
                </h3>

                {isLoading ? (
                    <div className="text-muted-foreground py-4 text-center text-sm">
                        {t('Loading comments...')}
                    </div>
                ) : comments.length === 0 ? (
                    <div className="text-muted-foreground py-4 text-center text-sm">
                        {t('No comments yet.')}
                    </div>
                ) : (
                    <div className="space-y-4">
                        {comments.map((comment) => (
                            <div
                                key={comment.id}
                                className="bg-background flex gap-4 rounded-lg border p-4 shadow-xs"
                            >
                                <div className="flex-1 space-y-1">
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm font-medium">
                                            {comment.author?.name ||
                                                'Unknown User'}
                                        </p>
                                        <p className="text-muted-foreground font-mono text-xs">
                                            {format(
                                                new Date(comment.created_at),
                                                "MMM d, yyyy 'at' h:mm a",
                                            )}
                                        </p>
                                    </div>
                                    <div className="text-foreground mt-2 text-sm leading-relaxed whitespace-pre-wrap">
                                        <ReferenceText
                                            text={comment.body}
                                            highlightUserMentions
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Komentar: "@" untuk mention anggota tim, project, atau dashboard katalog. */}
            <form
                onSubmit={handleSubmit}
                className="relative space-y-3 border-t pt-4"
            >
                <ReferenceTextarea
                    placeholder={t(
                        'Add a comment... Type @ to mention a team member, project, or dashboard',
                    )}
                    value={newComment}
                    onChange={setNewComment}
                    people={users}
                    placement="top"
                    rows={3}
                    disabled={isAdding}
                />

                <div className="flex items-center justify-between">
                    <span className="text-muted-foreground text-[11px]">
                        {t(
                            'Tip: @ mentions colleagues, projects, and catalog dashboards',
                        )}
                    </span>
                    <Button
                        type="submit"
                        size="sm"
                        disabled={isAdding || !newComment.trim()}
                        className="gap-1.5"
                    >
                        <Send className="h-3.5 w-3.5" />
                        <span>
                            {isAdding ? t('Posting...') : t('Post Comment')}
                        </span>
                    </Button>
                </div>
            </form>
        </div>
    );
};
