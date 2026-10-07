import { useState, useEffect, useMemo } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import { Role, useSyncPermissions, useSyncMenus } from '../../hooks/use-roles';
import { usePermissions } from '../../hooks/use-permissions';
import { useMenus } from '../../hooks/use-menus';
import axios from '@/lib/axios';
import { isAxiosError } from 'axios';
import { useTranslate } from "@/hooks/useTranslate";

interface ManageRoleAccessDialogProps {
    role: Role | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function ManageRoleAccessDialog({ role, open, onOpenChange }: ManageRoleAccessDialogProps) {
    const { t } = useTranslate();
    const { data: permissions } = usePermissions();
    const { data: menus } = useMenus();
    const syncPermissions = useSyncPermissions();
    const syncMenus = useSyncMenus();

    const [selectedPermissionIds, setSelectedPermissionIds] = useState<string[]>([]);
    const [selectedMenuIds, setSelectedMenuIds] = useState<string[]>([]);
    
    const [openPermissions, setOpenPermissions] = useState(false);
    const [openMenus, setOpenMenus] = useState(false);

    useEffect(() => {
        if (role && open) {
            setSelectedPermissionIds(role.permissions?.map(p => p.id) || []);
            setSelectedMenuIds(role.menus?.map(m => m.id) || []);
            setOpenPermissions(false);
            setOpenMenus(false);
        }
    }, [role, open]);

    const handleSavePermissions = () => {
        if (!role) return;
        syncPermissions.mutate({
            roleId: role.id,
            permissionIds: selectedPermissionIds
        }, {
            onSuccess: () => {
                toast.success(t('Permissions updated successfully'));
            },
            onError: (error) => {
                if (isAxiosError(error)) {
                    toast.error(error.response?.data?.message || 'Failed to update permissions');
                } else {
                    toast.error(t('An error occurred while updating permissions'));
                }
            }
        });
    };

    const handleSaveMenus = () => {
        if (!role) return;
        syncMenus.mutate({
            roleId: role.id,
            menuIds: selectedMenuIds
        }, {
            onSuccess: () => {
                toast.success(t('Menus updated successfully'));
            },
            onError: (error) => {
                if (isAxiosError(error)) {
                    toast.error(error.response?.data?.message || 'Failed to update menus');
                } else {
                    toast.error(t('An error occurred while updating menus'));
                }
            }
        });
    };

    const togglePermission = (id: string) => {
        setSelectedPermissionIds(prev => 
            prev.includes(id) ? prev.filter(pId => pId !== id) : [...prev, id]
        );
    };

    const toggleMenu = (id: string) => {
        setSelectedMenuIds(prev => 
            prev.includes(id) ? prev.filter(mId => mId !== id) : [...prev, id]
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[650px] h-[600px] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Manage Access: {role?.name}</DialogTitle>
                </DialogHeader>

                <Tabs defaultValue="permissions" className="w-full flex-1 flex flex-col min-h-0">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="permissions">{t('Permissions')}</TabsTrigger>
                        <TabsTrigger value="menus">{t('Menus')}</TabsTrigger>
                    </TabsList>
                    
                    {/* PERMISSIONS TAB */}
                    <TabsContent value="permissions" className="flex-1 flex flex-col space-y-4 pt-4 min-h-0">
                        <div className="flex-1 flex flex-col space-y-3 min-h-0">
                            <label className="text-sm font-medium">{t('Select Permissions')}</label>
                            
                            <Popover open={openPermissions} onOpenChange={setOpenPermissions}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openPermissions}
                                        className="w-full justify-between h-auto min-h-10 py-2"
                                    >
                                        <div className="flex flex-wrap gap-1 items-center">
                                            {selectedPermissionIds.length === 0 && <span className="text-muted-foreground font-normal">{t('Select permissions...')}</span>}
                                            {selectedPermissionIds.length > 0 && selectedPermissionIds.length <= 3 && 
                                                selectedPermissionIds.map(id => {
                                                    const p = permissions?.find(x => x.id === id);
                                                    return p ? <Badge variant="secondary" key={id}>{p.name}</Badge> : null;
                                                })
                                            }
                                            {selectedPermissionIds.length > 3 && (
                                                <Badge variant="secondary">{selectedPermissionIds.length} {t('selected')}</Badge>
                                            )}
                                        </div>
                                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-[500px] p-0" align="start">
                                    <Command>
                                        <CommandInput placeholder={t('Search permissions...')} />
                                        <CommandList>
                                            <CommandEmpty>{t('No permissions found.')}</CommandEmpty>
                                            <CommandGroup>
                                                {permissions?.map((p) => (
                                                    <CommandItem
                                                        key={p.id}
                                                        value={p.name}
                                                        onSelect={() => togglePermission(p.id)}
                                                    >
                                                        <Check
                                                            className={cn(
                                                                "mr-2 h-4 w-4",
                                                                selectedPermissionIds.includes(p.id) ? "opacity-100" : "opacity-0"
                                                            )}
                                                        />
                                                        {p.name}
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>

                        </div>
                        <div className="flex justify-end pt-2">
                            <Button onClick={handleSavePermissions} disabled={syncPermissions.isPending}>
                                {syncPermissions.isPending ? 'Saving...' : 'Save Permissions'}
                            </Button>
                        </div>
                    </TabsContent>
                    
                    {/* MENUS TAB */}
                    <TabsContent value="menus" className="flex-1 flex flex-col space-y-4 pt-4 min-h-0">
                        <div className="flex-1 flex flex-col space-y-3 min-h-0">
                            <label className="text-sm font-medium">{t('Select Menus')}</label>
                            
                            <Popover open={openMenus} onOpenChange={setOpenMenus}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openMenus}
                                        className="w-full justify-between h-auto min-h-10 py-2"
                                    >
                                        <div className="flex flex-wrap gap-1 items-center">
                                            {selectedMenuIds.length === 0 && <span className="text-muted-foreground font-normal">{t('Select menus...')}</span>}
                                            {selectedMenuIds.length > 0 && selectedMenuIds.length <= 3 && 
                                                selectedMenuIds.map(id => {
                                                    const m = menus?.find(x => x.id === id);
                                                    return m ? <Badge variant="secondary" key={id}>{m.label}</Badge> : null;
                                                })
                                            }
                                            {selectedMenuIds.length > 3 && (
                                                <Badge variant="secondary">{selectedMenuIds.length} {t('selected')}</Badge>
                                            )}
                                        </div>
                                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-[500px] p-0" align="start">
                                    <Command>
                                        <CommandInput placeholder={t('Search menus...')} />
                                        <CommandList>
                                            <CommandEmpty>{t('No menus found.')}</CommandEmpty>
                                            <CommandGroup>
                                                {menus?.map((m) => (
                                                    <CommandItem
                                                        key={m.id}
                                                        value={`${m.label} ${m.key}`}
                                                        onSelect={() => toggleMenu(m.id)}
                                                    >
                                                        <Check
                                                            className={cn(
                                                                "mr-2 h-4 w-4",
                                                                selectedMenuIds.includes(m.id) ? "opacity-100" : "opacity-0"
                                                            )}
                                                        />
                                                        {m.label} <span className="text-xs text-muted-foreground ml-2">({m.key})</span>
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>

                        </div>
                        <div className="flex justify-end pt-2">
                            <Button onClick={handleSaveMenus} disabled={syncMenus.isPending}>
                                {syncMenus.isPending ? 'Saving...' : 'Save Menus'}
                            </Button>
                        </div>
                    </TabsContent>
                </Tabs>
            </DialogContent>
        </Dialog>
    );
}

