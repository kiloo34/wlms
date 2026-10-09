import React from 'react';
import { usePage } from '@inertiajs/react';
import { Issue } from '@/types/issue';
import { SharedData } from '@/types';
import { Button } from '@/components/ui/button';
import { SelectOption } from './IssueFormDialog';
import { PriorityBadge } from '@/components/PriorityBadge';
import { useTranslate } from "@/hooks/useTranslate";
import { useIconSize } from '@/hooks/use-appearance';

interface IssueListProps {
    issues: Issue[];
    isLoading: boolean;
    onCreateClick?: () => void;
    onEdit: (issue: Issue) => void;
    onDelete: (id: string) => void;
    onLogWork: (issue: Issue) => void;
    // Map dictionaries for rendering
    issueTypes: SelectOption[];
    priorities: SelectOption[];
    statuses: SelectOption[];
    assignees: SelectOption[];
}

export const IssueList: React.FC<IssueListProps> = ({
    issues,
    isLoading,
    onCreateClick,
    onEdit,
    onDelete,
    onLogWork,
    issueTypes,
    priorities,
    statuses,
    assignees,
}) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const { auth } = usePage<SharedData>().props;
    const currentUserId: number | null = auth.user?.id ?? null;

    const cellPaddingClass =
        iconSize === 'sm'
            ? 'px-3.5 py-2 text-xs'
            : iconSize === 'lg'
            ? 'px-6 py-4 text-base'
            : 'px-5 py-3 text-sm';

    const headerPaddingClass =
        iconSize === 'sm'
            ? 'px-3.5 py-2 text-[11px]'
            : iconSize === 'lg'
            ? 'px-6 py-3.5 text-sm'
            : 'px-5 py-2.5 text-xs';

    const actionButtonClass =
        iconSize === 'sm'
            ? 'h-7 text-xs px-2'
            : iconSize === 'lg'
            ? 'h-9 text-sm px-3.5'
            : 'h-8 text-xs px-2.5';

    const createBtnClass =
        iconSize === 'sm'
            ? 'h-8 text-xs px-3'
            : iconSize === 'lg'
            ? 'h-10 text-sm px-4.5'
            : 'h-9 text-xs px-3.5';

    const getName = (options: SelectOption[], id: string | null) => {
        if (!id) return '-';
        return options.find(o => o.id === id)?.name || id;
    };

    if (isLoading) {
        return <div className="text-center p-4">{t('Loading issues...')}</div>;
    }

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <h3 className={`${iconSize === 'sm' ? 'text-base font-semibold' : iconSize === 'lg' ? 'text-xl font-bold' : 'text-lg font-medium'}`}>{t('Issues')}</h3>
                {onCreateClick && (
                    <Button onClick={onCreateClick} className={createBtnClass}>{t('Create Task')}</Button>
                )}
            </div>

            {(!issues || issues.length === 0) ? (
                <div className="text-center p-4 border rounded-md bg-muted">
                    {t('No issues found.')}
                </div>
            ) : (
                <div className="overflow-x-auto rounded-md border">
                    <table className="min-w-full divide-y divide-border">
                <thead className="bg-muted">
                    <tr>
                        <th className={`${headerPaddingClass} text-left font-medium text-muted-foreground uppercase tracking-wider`}>{t('Title')}</th>
                        <th className={`${headerPaddingClass} text-left font-medium text-muted-foreground uppercase tracking-wider`}>{t('Type')}</th>
                        <th className={`${headerPaddingClass} text-left font-medium text-muted-foreground uppercase tracking-wider`}>{t('Status')}</th>
                        <th className={`${headerPaddingClass} text-left font-medium text-muted-foreground uppercase tracking-wider`}>{t('Priority')}</th>
                        <th className={`${headerPaddingClass} text-left font-medium text-muted-foreground uppercase tracking-wider`}>{t('Assignee')}</th>
                        <th className={`${headerPaddingClass} text-right font-medium text-muted-foreground uppercase tracking-wider`}>{t('Actions')}</th>
                    </tr>
                </thead>
                <tbody className="bg-card divide-y divide-border">
                    {issues.map((issue) => {
                        const priorityItem = priorities.find(p => p.id === issue.priority_id);
                        return (
                            <tr key={issue.id} className="hover:bg-muted/40 transition-colors">
                                <td className={`${cellPaddingClass} whitespace-nowrap font-medium text-foreground`}>
                                    <span onClick={() => onEdit(issue)} className="hover:underline hover:text-primary cursor-pointer">
                                        {issue.title}
                                    </span>
                                </td>
                                <td className={`${cellPaddingClass} whitespace-nowrap text-muted-foreground`}>
                                    <span className={`inline-flex items-center rounded font-normal bg-muted/50 text-muted-foreground border border-border/50 ${
                                        iconSize === 'sm' ? 'px-1.5 py-0 text-[10px]' : iconSize === 'lg' ? 'px-2.5 py-0.5 text-xs' : 'px-2 py-0.5 text-[11px]'
                                    }`}>
                                        {getName(issueTypes, issue.issue_type_id)}
                                    </span>
                                </td>
                                <td className={`${cellPaddingClass} whitespace-nowrap text-muted-foreground`}>
                                    <span className={`inline-flex items-center rounded font-medium bg-muted text-foreground border border-border/50 ${
                                        iconSize === 'sm' ? 'px-1.5 py-0 text-[10px]' : iconSize === 'lg' ? 'px-2.5 py-1 text-xs' : 'px-2 py-0.5 text-xs'
                                    }`}>
                                        {getName(statuses, issue.status_id)}
                                    </span>
                                </td>
                                <td className={`${cellPaddingClass} whitespace-nowrap`}>
                                    {issue.priority_id && priorityItem ? (
                                        <PriorityBadge 
                                            name={priorityItem.name} 
                                            category={priorityItem.name} 
                                            size={iconSize} 
                                        />
                                    ) : (
                                        <span className="text-muted-foreground text-xs">-</span>
                                    )}
                                </td>
                                <td className={`${cellPaddingClass} whitespace-nowrap text-muted-foreground`}>
                                    {getName(assignees, issue.assignee_id)}
                                </td>
                                <td className={`${cellPaddingClass} whitespace-nowrap text-right font-medium space-x-1.5`}>
                                    {currentUserId && issue.assignee_id && String(currentUserId) === String(issue.assignee_id) && (
                                        <Button variant="outline" size="sm" className={actionButtonClass} onClick={() => onLogWork(issue)}>
                                            {t('Log Work')}
                                        </Button>
                                    )}
                                    <Button variant="outline" size="sm" className={actionButtonClass} onClick={() => onEdit(issue)}>
                                        {t('Edit')}
                                    </Button>
                                    <Button variant="destructive" size="sm" className={actionButtonClass} onClick={() => {
                                        if (window.confirm('Are you sure you want to delete this issue?')) {
                                            onDelete(issue.id);
                                        }
                                    }}>
                                        {t('Delete')}
                                    </Button>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
        )}
        </div>
    );
};

