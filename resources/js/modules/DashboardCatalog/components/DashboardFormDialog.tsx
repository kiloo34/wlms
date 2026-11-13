import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslate } from '@/hooks/useTranslate';
import {
    toFormData,
    type DashboardCatalogOptions,
    type TableauDashboard,
    type TableauDashboardFormData,
} from '@/modules/DashboardCatalog/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** null = mode tambah, object = mode ubah */
    dashboard: TableauDashboard | null;
    options: DashboardCatalogOptions;
    nextNumber: number;
}

/**
 * Dialog tambah / ubah entri Katalog Dashboard Tableau.
 * Kolom mengikuti sheet Daily Check Tableau.
 */
export function DashboardFormDialog({
    open,
    onOpenChange,
    dashboard,
    options,
    nextNumber,
}: Props) {
    const { t } = useTranslate();
    const isEdit = dashboard !== null;

    const form = useForm<TableauDashboardFormData>(
        toFormData(dashboard, nextNumber),
    );
    const { data, setData, errors, processing, reset, clearErrors } = form;

    useEffect(() => {
        if (open) {
            form.setDefaults(toFormData(dashboard, nextNumber));
            reset();
            clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, dashboard?.id, nextNumber]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const callbacks = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    isEdit
                        ? t('Dashboard updated successfully')
                        : t('Dashboard recorded successfully'),
                );
                onOpenChange(false);
            },
            onError: () => {
                toast.error(t('Please check the form for errors'));
            },
        };

        if (isEdit) {
            form.put(`/dashboards/${dashboard.id}`, callbacks);
        } else {
            form.post('/dashboards', callbacks);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[92vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEdit ? t('Edit Dashboard') : t('Register Dashboard')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'Fill in the dashboard details as listed in the Tableau catalog sheet.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-5">
                    <div className="grid gap-4 sm:grid-cols-[110px_1fr]">
                        <div className="grid gap-2">
                            <Label htmlFor="number">{t('Number')}</Label>
                            <Input
                                id="number"
                                type="number"
                                min={1}
                                value={data.number}
                                onChange={(e) =>
                                    setData('number', e.target.value)
                                }
                            />
                            <InputError message={errors.number} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                {t('Dashboard Name')}{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                value={data.name}
                                autoFocus
                                required
                                placeholder={t('e.g. Dashboard Kredit Cabang')}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                            />
                            <InputError message={errors.name} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="tableau_project">
                                {t('Tableau Project')}
                            </Label>
                            <Input
                                id="tableau_project"
                                list="catalog-projects"
                                value={data.tableau_project}
                                placeholder={t('e.g. Dashboard DPK')}
                                onChange={(e) =>
                                    setData('tableau_project', e.target.value)
                                }
                            />
                            <datalist id="catalog-projects">
                                {options.projects.map((project) => (
                                    <option key={project} value={project} />
                                ))}
                            </datalist>
                            <InputError message={errors.tableau_project} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="mapping">{t('Mapping')}</Label>
                            <Input
                                id="mapping"
                                list="catalog-mappings"
                                value={data.mapping}
                                placeholder={t('e.g. Business Funding')}
                                onChange={(e) =>
                                    setData('mapping', e.target.value)
                                }
                            />
                            <datalist id="catalog-mappings">
                                {options.mappings.map((mapping) => (
                                    <option key={mapping} value={mapping} />
                                ))}
                            </datalist>
                            <InputError message={errors.mapping} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="definition">{t('Definition')}</Label>
                        <Textarea
                            id="definition"
                            rows={2}
                            value={data.definition}
                            placeholder={t(
                                'What the dashboard is about, e.g. Monitoring DPK valuta asing.',
                            )}
                            onChange={(e) =>
                                setData('definition', e.target.value)
                            }
                        />
                        <InputError message={errors.definition} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="contents">{t('Contents')}</Label>
                        <Textarea
                            id="contents"
                            rows={3}
                            value={data.contents}
                            placeholder={t(
                                'Data shown on the dashboard, e.g. posisi DPK valas per wilayah dan cabang.',
                            )}
                            onChange={(e) =>
                                setData('contents', e.target.value)
                            }
                        />
                        <InputError message={errors.contents} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>{t('Purpose')}</Label>
                            <Select
                                value={data.purpose || undefined}
                                onValueChange={(value) =>
                                    setData('purpose', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder={t('Select purpose')}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.purposes.map((purpose) => (
                                        <SelectItem
                                            key={purpose}
                                            value={purpose}
                                        >
                                            {purpose}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.purpose} />
                        </div>
                        <div className="grid gap-2">
                            <Label>{t('User Mapping')}</Label>
                            <Select
                                value={data.user_mapping || undefined}
                                onValueChange={(value) =>
                                    setData('user_mapping', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder={t('Select user mapping')}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.user_mappings.map((mapping) => (
                                        <SelectItem
                                            key={mapping}
                                            value={mapping}
                                        >
                                            {mapping}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.user_mapping} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="user_permission">
                            {t('User Permission')}
                        </Label>
                        <Textarea
                            id="user_permission"
                            rows={2}
                            value={data.user_permission}
                            placeholder={t(
                                'Divisions or branches with access, separated by commas.',
                            )}
                            onChange={(e) =>
                                setData('user_permission', e.target.value)
                            }
                        />
                        <InputError message={errors.user_permission} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="owner">{t('Owner')}</Label>
                            <Input
                                id="owner"
                                list="catalog-owners"
                                value={data.owner}
                                placeholder={t('e.g. Dana & Jasa')}
                                onChange={(e) =>
                                    setData('owner', e.target.value)
                                }
                            />
                            <datalist id="catalog-owners">
                                {options.owners.map((owner) => (
                                    <option key={owner} value={owner} />
                                ))}
                            </datalist>
                            <InputError message={errors.owner} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="notes">{t('Notes')}</Label>
                            <Input
                                id="notes"
                                value={data.notes}
                                placeholder={t('Optional remarks')}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                            />
                            <InputError message={errors.notes} />
                        </div>
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                        >
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {isEdit ? t('Save changes') : t('Save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
