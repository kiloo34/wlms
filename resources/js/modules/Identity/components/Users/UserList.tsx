import React, { useMemo } from 'react';
import type { User } from '../../hooks/use-users';
import type { Role } from '../../hooks/use-roles';
import { UserAssignRoleDialog } from './UserAssignRoleDialog';
import { UserAssignOrgUnitDialog } from './UserAssignOrgUnitDialog';
import { ColumnDef } from '@tanstack/react-table';
import { DataTable } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowUpDown, Building2 } from 'lucide-react';

interface UserListProps {
    users: User[];
    roles: Role[];
    orgUnits: { id: string; name: string }[];
    isAssigning: boolean;
    isUpdating?: boolean;
    onAssignRole: (payload: { user_id: string; role_id: string }) => Promise<void>;
    onUpdateUser?: (payload: { id: string; org_unit_id: string | null }) => Promise<void>;
}

export function UserList({ users, roles, orgUnits, isAssigning, isUpdating = false, onAssignRole, onUpdateUser }: UserListProps) {
    const columns = useMemo<ColumnDef<User>[]>(
        () => [
            {
                accessorKey: 'name',
                header: ({ column }) => (
                    <Button
                        variant="ghost"
                        onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
                        className="-ml-4"
                    >
                        Name
                        <ArrowUpDown className="ml-2 h-4 w-4" />
                    </Button>
                ),
                cell: ({ row }) => <div className="font-medium">{row.getValue('name')}</div>,
            },
            {
                accessorKey: 'email',
                header: 'Email',
                cell: ({ row }) => (
                    <span className="text-muted-foreground">{row.getValue('email')}</span>
                ),
            },
            {
                accessorKey: 'org_unit_name',
                header: 'Org Unit',
                cell: ({ row }) => {
                    const name = row.original.org_unit_name;
                    return name ? (
                        <div className="flex items-center gap-1.5 text-sm">
                            <Building2 className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                            <span>{name}</span>
                        </div>
                    ) : (
                        <span className="text-muted-foreground text-xs">—</span>
                    );
                },
            },
            {
                accessorKey: 'roles',
                header: 'Roles',
                cell: ({ row }) => {
                    const userRoles: string[] = row.original.roles ?? [];
                    if (userRoles.length === 0) {
                        return <span className="text-muted-foreground text-xs">No role</span>;
                    }
                    return (
                        <div className="flex flex-wrap gap-1">
                            {userRoles.map((r) => (
                                <Badge key={r} variant="secondary" className="text-xs">
                                    {r}
                                </Badge>
                            ))}
                        </div>
                    );
                },
            },
            {
                id: 'actions',
                header: () => <div className="text-right">Actions</div>,
                cell: ({ row }) => {
                    const user = row.original;
                    return (
                        <div className="flex justify-end items-center gap-2">
                            {onUpdateUser && (
                                <UserAssignOrgUnitDialog
                                    user={user}
                                    orgUnits={orgUnits}
                                    isSubmitting={isUpdating}
                                    onSubmit={onUpdateUser}
                                />
                            )}
                            <UserAssignRoleDialog
                                user={user}
                                roles={roles}
                                isSubmitting={isAssigning}
                                onSubmit={onAssignRole}
                            />
                        </div>
                    );
                },
            },
        ],
        [roles, orgUnits, isAssigning, isUpdating, onAssignRole, onUpdateUser]
    );

    return (
        <div className="w-full">
            <DataTable columns={columns} data={users} searchKey="name" />
        </div>
    );
}

