import { IssueTimeline } from './IssueTimeline';
import { CommentBox } from './CommentBox';

type Props = {
    issueId: string;
};

export function CommentThread({ issueId }: Props) {
    return (
        <div className="space-y-8">
            <h3 className="text-lg font-semibold">Activity</h3>
            
            <IssueTimeline issueId={issueId} />
            
            <div className="pt-4 border-t">
                <CommentBox issueId={issueId} />
            </div>
        </div>
    );
}

