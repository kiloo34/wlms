import { Pencil } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useTranslate } from '@/hooks/useTranslate';
import {
    DEFAULT_MAPPING_BADGE_CLASS,
    MAPPING_BADGE_CLASS,
    type TableauDashboard,
} from '@/modules/DashboardCatalog/types';

interface Props {
    dashboard: TableauDashboard | null;
    onOpenChange: (open: boolean) => void;
    canEdit: boolean;
    onEdit: (dashboard: TableauDashboard) => void;
}

/** Panel detail satu dashboard (semua kolom dari sheet). */
export function DashboardDetailSheet({
    dashboard,
    onOpenChange,
    canEdit,
    onEdit,
}: Props) {
    const { t } = useTranslate();
    const empty = (
        <span className="text-muted-foreground italic">{t('Not set')}</span>
    );

    const fields: { label: string; value: React.ReactNode }[] = dashboard
        ? [
              { label: t('Definition'), value: dashboard.definition || empty },
              { label: t('Contents'), value: dashboard.contents || empty },
              { label: t('Purpose'), value: dashboard.purpose || empty },
              {
                  label: t('Tableau Project'),
                  value: dashboard.tableau_project || empty,
              },
              {
                  label: t('User Permission'),
                  value: dashboard.user_permission || empty,
              },
              {
                  label: t('User Mapping'),
                  value: dashboard.user_mapping || empty,
              },
              { label: t('Owner'), value: dashboard.owner || empty },
              { label: t('Notes'), value: dashboard.notes || empty },
          ]
        : [];

    return (
        <Sheet open={dashboard !== null} onOpenChange={onOpenChange}>
            <SheetContent className="w-full overflow-y-auto sm:max-w-lg">
                {dashboard && (
                    <>
                        <SheetHeader>
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="outline">
                                    #{dashboard.number ?? '-'}
                                </Badge>
                                {dashboard.mapping && (
                                    <Badge
                                        variant="outline"
                                        className={
                                            MAPPING_BADGE_CLASS[
                                                dashboard.mapping
                                            ] ?? DEFAULT_MAPPING_BADGE_CLASS
                                        }
                                    >
                                        {dashboard.mapping}
                                    </Badge>
                                )}
                            </div>
                            <SheetTitle className="text-left leading-snug">
                                {dashboard.name}
                            </SheetTitle>
                            <SheetDescription className="text-left">
                                {t('Dashboard details')}
                            </SheetDescription>
                        </SheetHeader>

                        <dl className="grid gap-4 px-4 pb-4">
                            {fields.map((field) => (
                                <div key={field.label} className="grid gap-1">
                                    <dt className="text-muted-foreground text-[11px] font-semibold tracking-wide uppercase">
                                        {field.label}
                                    </dt>
                                    <dd className="text-sm leading-relaxed whitespace-pre-line">
                                        {field.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>

                        {canEdit && (
                            <div className="px-4 pb-4">
                                <Button
                                    className="w-full"
                                    onClick={() => onEdit(dashboard)}
                                >
                                    <Pencil className="size-4" />
                                    {t('Edit Dashboard')}
                                </Button>
                            </div>
                        )}
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
