const fs = require('fs');
const file = 'resources/js/modules/Workload/Issues/components/IssuesManager.tsx';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(
    /List\n                    <\/Button>\n                <\/div>\n            <\/div>/,
    `List
                    </Button>
                </div>
                </div>
            </div>`
);

content = content.replace(
    /<ProjectActivitySheet[\s\S]*?\/>/,
    `<ProjectActivitySheet
                projectId={projectId}
                isOpen={isActivityOpen}
                onClose={() => setIsActivityOpen(false)}
            />
        </div>` // <-- Closing the main <div className="w-full space-y-4"> because it was missed!
);

fs.writeFileSync(file, content);
