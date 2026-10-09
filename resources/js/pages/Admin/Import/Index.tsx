import { Head, useForm } from '@inertiajs/react';
import { useTranslate } from '@/hooks/useTranslate';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { useGetWorkspaces } from '@/modules/Workload/Workspaces/hooks/useWorkspaces';
import { Workspace } from '@/modules/Workload/Workspaces/types';
import { FormEvent } from 'react';

export default function ImportIndex() {
    const { t } = useTranslate();
    const { data: workspaces = [], isLoading: isLoadingWorkspaces } = useGetWorkspaces();

    const { data, setData, post, processing, errors } = useForm<{
        type: 'projects' | 'issues';
        workspace_id: string;
        file: File | null;
    }>({
        type: 'projects',
        workspace_id: '',
        file: null,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/import-workload', {
            forceFormData: true,
            onSuccess: (page: any) => {
                if (page.props.flash?.success) {
                    toast.success(page.props.flash.success);
                }
            },
            onError: (err) => {
                toast.error(t('Failed to import data'));
            }
        });
    };

    return (
        <>
            <Head title={t('Import Workload')} />
            <div className="space-y-8 max-w-4xl">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Import Workload Data')}</CardTitle>
                        <CardDescription>
                            {t('Upload .csv or .xlsx file to import Projects or Tasks.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-3">
                                <Label>{t('Import Type')}</Label>
                                <Select 
                                    value={data.type} 
                                    onValueChange={(val: 'projects' | 'issues') => {
                                        setData('type', val);
                                        if (val !== 'projects') {
                                            setData('workspace_id', '');
                                        }
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select import type')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="projects">{t('Projects')}</SelectItem>
                                        <SelectItem value="issues">{t('Tasks (Issues)')}</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.type && <p className="text-sm text-destructive">{errors.type}</p>}
                            </div>

                            {data.type === 'projects' && (
                                <div className="space-y-3">
                                    <Label>{t('Target Workspace')}</Label>
                                    <Select 
                                        value={data.workspace_id} 
                                        onValueChange={(val) => setData('workspace_id', val)}
                                        disabled={isLoadingWorkspaces}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Select a workspace')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {workspaces.map((workspace: Workspace) => (
                                                <SelectItem key={workspace.id} value={workspace.id.toString()}>
                                                    {workspace.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.workspace_id && <p className="text-sm text-destructive">{errors.workspace_id}</p>}
                                </div>
                            )}

                            <div className="space-y-3">
                                <Label>{t('File (.csv, .xlsx)')}</Label>
                                <Input 
                                    type="file" 
                                    accept=".csv, .xlsx" 
                                    onChange={(e) => setData('file', e.target.files ? e.target.files[0] : null)}
                                />
                                {errors.file && <p className="text-sm text-destructive">{errors.file}</p>}
                            </div>

                            <Button type="submit" disabled={processing || !data.file || (data.type === 'projects' && !data.workspace_id)}>
                                {processing ? t('Importing...') : t('Import Data')}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ImportIndex.layout = {
    breadcrumbs: [
        { title: "Import Workload", href: "/admin/import-workload" }
    ]
};
