const fs = require('fs');
const file = 'resources/js/modules/Workload/Issues/components/IssuesManager.tsx';
let content = fs.readFileSync(file, 'utf8');

// Imports
content = content.replace(
    "import { Button } from '@/components/ui/button';",
    "import { Button } from '@/components/ui/button';\nimport { Input } from '@/components/ui/input';\nimport { Progress } from '@/components/ui/progress';\nimport { Search, Activity } from 'lucide-react';\nimport { ProjectActivitySheet } from './ProjectActivitySheet';"
);

// State and calculation
content = content.replace(
    "const [selectedLogWorkIssue, setSelectedLogWorkIssue] = useState<Issue | null>(null);",
    `const [selectedLogWorkIssue, setSelectedLogWorkIssue] = useState<Issue | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [isActivityOpen, setIsActivityOpen] = useState(false);

    const filteredIssues = issues.filter(issue => 
        issue.title.toLowerCase().includes(searchQuery.toLowerCase())
    );

    const doneStatusIds = lookups.statuses
        .filter(s => ['done', 'closed', 'resolved'].includes(s.name.toLowerCase()))
        .map(s => s.id);
    
    const finalDoneIds = doneStatusIds.length > 0 ? doneStatusIds : [lookups.statuses[lookups.statuses.length - 1]?.id];

    const totalIssues = issues.length;
    const closedIssues = issues.filter(issue => finalDoneIds.includes(issue.status_id)).length;
    const progressPercentage = totalIssues === 0 ? 0 : Math.round((closedIssues / totalIssues) * 100);`
);

// Header changes
content = content.replace(
    /<div className="flex justify-between items-center">[\s\S]*?<div className="flex gap-2 bg-gray-100 p-1 rounded-md dark:bg-gray-800">/,
    `<div className="flex flex-col gap-4">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-4 w-full sm:w-auto">
                        <div className="relative w-full sm:w-72">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Search issues..."
                                className="pl-8"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                        <Button variant="outline" size="sm" onClick={() => setIsActivityOpen(true)} className="shrink-0 flex items-center gap-2">
                            <Activity className="h-4 w-4" />
                            <span className="hidden sm:inline">Activity History</span>
                        </Button>
                    </div>

                    <div className="flex gap-2 bg-gray-100 p-1 rounded-md dark:bg-gray-800 shrink-0">`
);

// Progress bar
content = content.replace(
    "List\n                    </Button>\n                </div>\n            </div>",
    `List
                    </Button>
                </div>
            </div>

            <div className="flex items-center gap-4 bg-card border rounded-lg p-4">
                <div className="flex-1">
                    <div className="flex justify-between mb-2">
                        <span className="text-sm font-medium">Project Progress</span>
                        <span className="text-sm font-medium">{progressPercentage}%</span>
                    </div>
                    <Progress value={progressPercentage} className="h-2" />
                </div>
                <div className="text-sm text-muted-foreground text-right min-w-[100px]">
                    {closedIssues} / {totalIssues} Done
                </div>
            </div>`
);

// Issues list/board replacement
content = content.replace(
    "issues={issues}",
    "issues={filteredIssues}"
);
content = content.replace(
    "issues={issues}",
    "issues={filteredIssues}"
);

// Add Activity Sheet
content = content.replace(
    "</LogWorkDialog>",
    `</LogWorkDialog>\n\n            <ProjectActivitySheet\n                projectId={projectId}\n                isOpen={isActivityOpen}\n                onClose={() => setIsActivityOpen(false)}\n            />`
);

fs.writeFileSync(file, content);
console.log('IssuesManager updated');
