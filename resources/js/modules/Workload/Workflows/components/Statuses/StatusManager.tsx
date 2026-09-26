import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-react';
import { StatusList } from './StatusList';
import { StatusFormDialog } from './StatusFormDialog';
import { useStatuses, useCreateStatus, useUpdateStatus, useDeleteStatus } from '../../hooks/useStatuses';
import type { Status, CreateStatusDto, UpdateStatusDto } from '../../types';

export function StatusManager() {
    const { data: statuses = [], isLoading } = useStatuses();
    const createStatus = useCreateStatus();
    const updateStatus = useUpdateStatus();
    const deleteStatus = useDeleteStatus();

    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedStatus, setSelectedStatus] = useState<Status | null>(null);

    const handleOpenCreate = () => {
        setSelectedStatus(null);
        setIsFormOpen(true);
    };

    const handleOpenEdit = (status: Status) => {
        setSelectedStatus(status);
        setIsFormOpen(true);
    };

    const handleDelete = (status: Status) => {
        if (confirm(`Are you sure you want to delete status "${status.name}"?`)) {
            deleteStatus.mutate(status.id);
        }
    };

    const handleSubmit = (payload: CreateStatusDto | UpdateStatusDto) => {
        if (selectedStatus) {
            updateStatus.mutate({ id: selectedStatus.id, payload }, {
                onSuccess: () => setIsFormOpen(false)
            });
        } else {
            createStatus.mutate(payload as CreateStatusDto, {
                onSuccess: () => setIsFormOpen(false)
            });
        }
    };

    if (isLoading) {
        return <div className="py-4">Loading statuses...</div>;
    }

    return (
        <div className="space-y-4">
            <div className="flex justify-end">
                <Button onClick={handleOpenCreate}>
                    <Plus className="h-4 w-4 mr-2" />
                    Create Status
                </Button>
            </div>
            
            <StatusList 
                statuses={statuses} 
                onEdit={handleOpenEdit} 
                onDelete={handleDelete} 
            />

            <StatusFormDialog
                open={isFormOpen}
                onOpenChange={setIsFormOpen}
                status={selectedStatus}
                onSubmit={handleSubmit}
                isLoading={createStatus.isPending || updateStatus.isPending}
            />
        </div>
    );
}
