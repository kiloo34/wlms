import { usePage } from '@inertiajs/react';

export function usePermission() {
    const { auth } = usePage().props as any;
    const permissions: string[] = auth?.user?.permissions ?? [];
    const roles: string[] = auth?.user?.roles ?? [];

    const can = (permission: string): boolean =>
        permissions.includes('*') ||
        permissions.includes(permission) ||
        roles.some((r) => r.toLowerCase() === 'superadmin');

    const hasRole = (roleName: string): boolean =>
        roles.some((r) => r.toLowerCase() === roleName.toLowerCase()) ||
        permissions.includes('*');

    return { can, hasRole, permissions, roles };
}

