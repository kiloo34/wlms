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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Loader2, Trash2, Shield, User } from 'lucide-react';
import { WorkspaceMember } from '../hooks/useWorkspaceMembers';
import { useTranslate } from '@/hooks/useTranslate';

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
    onUpdateRole?: (userId: string, role: string) => void;
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
    onUpdateRole,
    isUpdating
}: WorkspaceMembersUIProps) {
    const { t } = useTranslate();
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[700px]">
                <DialogHeader>
                    <DialogTitle>{t('Manage Members')}</DialogTitle>
                    <DialogDescription>
                        {t('Manage members for :name workspace.', { name: workspaceName })}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6 py-4">
                    <div className="flex items-end gap-3">
                        <div className="flex-1 space-y-2">
                            <label className="text-sm font-medium">{t('Invite User')}</label>
                            <Combobox
                                options={userOptions}
                                value={selectedUserId}
                                onChange={onSelectedUserIdChange}
                                placeholder={t('Select user to invite...')}
                                emptyText={t('No users found.')}
                            />
                        </div>
                        <Button 
                            onClick={onAddMember} 
                            disabled={!selectedUserId || isAdding}
                        >
                            {isAdding && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            {t('Add to Workspace')}
                        </Button>
                    </div>

                    <div className="space-y-3">
                        <h4 className="text-sm font-medium">{t('Current Members')} ({members.length})</h4>
                        
                        <div className="rounded-md border divide-y">
                            {isLoadingMembers ? (
                                <div className="p-4 text-center text-sm text-muted-foreground flex items-center justify-center">
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    {t('Loading members...')}
                                </div>
                            ) : members.length === 0 ? (
                                <div className="p-4 text-center text-sm text-muted-foreground">
                                    {t('No members found.')}
                                </div>
                            ) : (
                                members.map(member => (
                                    <div key={member.id} className="flex items-center justify-between p-3 gap-4">
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium truncate">{member.name}</p>
                                            <p className="text-xs text-muted-foreground truncate">{member.email}</p>
                                        </div>
                                        <div className="flex items-center gap-3 shrink-0">
                                            <div className="flex items-center gap-1.5" title={t('Daily capacity (hours)')}>
                                                <span className="text-xs text-muted-foreground hidden sm:inline">{t('Hours')}:</span>
                                                <Input 
                                                    type="number" 
                                                    min="0" 
                                                    max="24"
                                                    className="w-14 h-8 text-xs text-center px-1" 
                                                    defaultValue={member.daily_capacity_hours ?? 8}
                                                    onBlur={(e) => onUpdateCapacity?.(member.id, Number(e.target.value))}
                                                    disabled={isUpdating}
                                                />
                                            </div>
                                            
                                            <div className="w-[120px]">
                                                <Select
                                                    defaultValue={member.role || 'member'}
                                                    onValueChange={(val) => onUpdateRole?.(member.id, val)}
                                                    disabled={isUpdating}
                                                >
                                                    <SelectTrigger className="h-8 text-xs">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="admin">
                                                            <div className="flex items-center">
                                                                <Shield className="w-3 h-3 mr-2" />
                                                                {t('Admin')}
                                                            </div>
                                                        </SelectItem>
                                                        <SelectItem value="member">
                                                            <div className="flex items-center">
                                                                <User className="w-3 h-3 mr-2" />
                                                                {t('Member')}
                                                            </div>
                                                        </SelectItem>
                                                        <SelectItem value="viewer">
                                                            <div className="flex items-center">
                                                                <User className="w-3 h-3 mr-2 text-muted-foreground" />
                                                                {t('Viewer')}
                                                            </div>
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            
                                            <Button 
                                                variant="ghost" 
                                                size="icon" 
                                                className="h-8 w-8 text-destructive shrink-0"
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
