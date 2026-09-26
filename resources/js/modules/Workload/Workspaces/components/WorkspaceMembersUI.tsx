import React from 'react';
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogDescription 
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Combobox } from '@/components/ui/combobox';
import { Loader2, Trash2 } from 'lucide-react';
import { WorkspaceMember } from '../hooks/useWorkspaceMembers';

export interface WorkspaceMembersUIProps {
    workspaceName: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    
    // Members Data
    members: WorkspaceMember[];
    isLoadingMembers: boolean;
    
    // Add Member form state
    userOptions: { value: string; label: string }[];
    selectedUserId: string;
    onSelectedUserIdChange: (id: string) => void;
    
    // Actions
    onAddMember: () => void;
    isAdding: boolean;
    onRemoveMember: (userId: string) => void;
    isRemoving: boolean;
    onUpdateCapacity?: (userId: string, hours: number) => void;
    isUpdating?: boolean;
}

export function WorkspaceMembersUI({
    workspaceName,
    open,
    onOpenChange,
    members,
    isLoadingMembers,
    userOptions,
    selectedUserId,
    onSelectedUserIdChange,
    onAddMember,
    isAdding,
    onRemoveMember,
    isRemoving,
    onUpdateCapacity,
    isUpdating
}: WorkspaceMembersUIProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[650px]">
                <DialogHeader>
                    <DialogTitle>Manage Members</DialogTitle>
                    <DialogDescription>
                        Manage members for {workspaceName} workspace.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6 py-4">
                    <div className="flex items-end gap-3">
                        <div className="flex-1 space-y-2">
                            <label className="text-sm font-medium">Invite User</label>
                            <Combobox
                                options={userOptions}
                                value={selectedUserId}
                                onChange={onSelectedUserIdChange}
                                placeholder="Select user to invite..."
                                emptyText="No users found."
                            />
                        </div>
                        <Button 
                            onClick={onAddMember} 
                            disabled={!selectedUserId || isAdding}
                        >
                            {isAdding && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Add
                        </Button>
                    </div>

                    <div className="space-y-3">
                        <h4 className="text-sm font-medium">Current Members ({members.length})</h4>
                        
                        <div className="rounded-md border divide-y">
                            {isLoadingMembers ? (
                                <div className="p-4 text-center text-sm text-muted-foreground flex items-center justify-center">
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Loading members...
                                </div>
                            ) : members.length === 0 ? (
                                <div className="p-4 text-center text-sm text-muted-foreground">
                                    No members found.
                                </div>
                            ) : (
                                members.map(member => (
                                    <div key={member.id} className="flex items-center justify-between p-3">
                                        <div>
                                            <p className="text-sm font-medium">{member.name}</p>
                                            <p className="text-xs text-muted-foreground">{member.email}</p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <div className="flex items-center gap-1.5" title="Daily capacity (hours)">
                                                <span className="text-xs text-muted-foreground">Work hours:</span>
                                                <Input 
                                                    type="number" 
                                                    min="0" 
                                                    max="24"
                                                    className="w-16 h-8 text-xs text-center" 
                                                    defaultValue={member.daily_capacity_hours ?? 8}
                                                    onBlur={(e) => onUpdateCapacity?.(member.id, Number(e.target.value))}
                                                    disabled={isUpdating}
                                                />
                                            </div>
                                            <span className="text-xs font-medium px-2 py-1 bg-secondary rounded-full">
                                                {member.role || 'MEMBER'}
                                            </span>
                                            <Button 
                                                variant="ghost" 
                                                size="icon" 
                                                className="h-8 w-8 text-destructive"
                                                onClick={() => onRemoveMember(member.id)}
                                                disabled={isRemoving}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
