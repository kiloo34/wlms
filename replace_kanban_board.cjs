const fs = require('fs');
const file = 'resources/js/modules/Workload/Issues/components/KanbanBoard.tsx';
let content = fs.readFileSync(file, 'utf8');

// Update imports
content = content.replace(
    "import { SelectOption } from './IssueFormDialog';",
    `import { SelectOption } from './IssueFormDialog';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';`
);

// Update KanbanBoardProps
content = content.replace(
    "statuses: SelectOption[];",
    `statuses: SelectOption[];
    lookups?: {
        issueTypes: {id: string, name: string}[];
        priorities: {id: string, name: string}[];
        users: {id: string, name: string}[];
    };`
);

// Update SortableIssueCard signature and content
const sortableCardRegex = /const SortableIssueCard = \({ issue, onEdit, onLogWork }:.*?\) => \{([\s\S]*?)return \([\s\S]*?\);\n\};/;

const newSortableCard = `const SortableIssueCard = ({ issue, onEdit, onLogWork, lookups }: { issue: Issue, onEdit: (issue: Issue) => void, onLogWork: (issue: Issue) => void, lookups?: any }) => {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: issue.id, data: { type: 'Issue', issue } });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const typeName = lookups?.issueTypes?.find((t: any) => t.id === issue.issue_type_id)?.name || 'Task';
    const priorityName = lookups?.priorities?.find((p: any) => p.id === issue.priority_id)?.name || 'Normal';
    const assigneeName = lookups?.users?.find((u: any) => u.id === issue.assignee_id)?.name || 'Unassigned';

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
            className="p-4 mb-3 bg-card text-card-foreground border rounded-lg shadow-sm cursor-grab active:cursor-grabbing hover:border-primary/50 group relative flex flex-col gap-3 transition-colors"
        >
            <div className="font-medium text-sm leading-tight pr-10">{issue.title}</div>
            
            <div className="flex items-center justify-between mt-auto">
                <div className="flex items-center gap-2">
                    <Badge variant="outline" className="text-[10px] px-1.5 py-0 h-5 bg-muted/50 font-normal">
                        {typeName}
                    </Badge>
                    <span className="text-[10px] text-muted-foreground font-medium uppercase tracking-wider">
                        {priorityName}
                    </span>
                </div>
                
                <Avatar className="h-6 w-6 border bg-muted">
                    <AvatarFallback className="text-[10px] font-medium">
                        {assigneeName.substring(0, 2).toUpperCase()}
                    </AvatarFallback>
                </Avatar>
            </div>

            <div className="absolute top-2 right-2 hidden group-hover:flex space-x-2 bg-card/80 backdrop-blur-sm p-1 rounded-md border shadow-sm">
                <button 
                    className="text-xs text-green-600 hover:text-green-700 font-medium px-1"
                    onClick={(e) => { e.stopPropagation(); onLogWork(issue); }}
                >
                    Log
                </button>
                <button 
                    className="text-xs text-blue-600 hover:text-blue-700 font-medium px-1"
                    onClick={(e) => { e.stopPropagation(); onEdit(issue); }}
                >
                    Edit
                </button>
            </div>
        </div>
    );
};`;

content = content.replace(sortableCardRegex, newSortableCard);

// Update KanbanColumn signature and content
const columnRegex = /const KanbanColumn = \({ status, issues, onEdit, onLogWork }:.*?\) => \{([\s\S]*?)return \([\s\S]*?\);\n\};/;

const newColumn = `const KanbanColumn = ({ status, issues, onEdit, onLogWork, lookups }: { status: SelectOption, issues: Issue[], onEdit: (issue: Issue) => void, onLogWork: (issue: Issue) => void, lookups?: any }) => {
    const { setNodeRef } = useSortable({
        id: status.id,
        data: { type: 'Column', status },
    });

    return (
        <div className="flex flex-col bg-muted/40 rounded-xl p-3 w-[300px] flex-shrink-0 border border-transparent hover:border-border/50 transition-colors">
            <div className="flex items-center justify-between mb-4 px-1">
                <div className="font-semibold text-sm text-foreground">
                    {status.name}
                </div>
                <div className="bg-muted text-muted-foreground text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium">
                    {issues.length}
                </div>
            </div>
            <div ref={setNodeRef} className="flex-1 overflow-y-auto min-h-[200px]">
                <SortableContext items={issues.map(i => i.id)} strategy={verticalListSortingStrategy}>
                    {issues.length > 0 ? (
                        issues.map(issue => (
                            <SortableIssueCard key={issue.id} issue={issue} onEdit={onEdit} onLogWork={onLogWork} lookups={lookups} />
                        ))
                    ) : (
                        <div className="h-24 border-2 border-dashed border-border rounded-lg flex items-center justify-center text-sm text-muted-foreground">
                            No issues here
                        </div>
                    )}
                </SortableContext>
            </div>
        </div>
    );
};`;

content = content.replace(columnRegex, newColumn);

// Update KanbanBoard export and props
content = content.replace(
    "export const KanbanBoard: React.FC<KanbanBoardProps> = ({ issues, statuses, onTransition, onEdit, onLogWork }) => {",
    "export const KanbanBoard: React.FC<KanbanBoardProps> = ({ issues, statuses, onTransition, onEdit, onLogWork, lookups }) => {"
);

// Update KanbanColumn call inside KanbanBoard
content = content.replace(
    "<KanbanColumn key={col.id} status={col} issues={col.issues} onEdit={onEdit} onLogWork={onLogWork} />",
    "<KanbanColumn key={col.id} status={col} issues={col.issues} onEdit={onEdit} onLogWork={onLogWork} lookups={lookups} />"
);

// Add DragOverlay lookups
content = content.replace(
    /<DragOverlay>[\s\S]*?<\/DragOverlay>/,
    `<DragOverlay>
                    {activeIssue ? (
                        <div className="opacity-80 rotate-2 scale-105">
                            <SortableIssueCard issue={activeIssue as Issue} onEdit={onEdit} onLogWork={onLogWork} lookups={lookups} />
                        </div>
                    ) : null}
                </DragOverlay>`
);

fs.writeFileSync(file, content);
console.log('KanbanBoard updated');
