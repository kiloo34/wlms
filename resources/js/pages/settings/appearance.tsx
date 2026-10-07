import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import { edit as editAppearance } from '@/routes/appearance';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslate } from "@/hooks/useTranslate";

export default function Appearance() {
    const { t } = useTranslate();
    return (
        <>
            <Head title={t('Appearance settings')} />

            <div className="space-y-8">
                {/* Appearance Card */}
                <Card>
                    <CardHeader className="border-b border-border pb-6">
                        <CardTitle>{t('Appearance Settings')}</CardTitle>
                        <CardDescription>
                            {t('Customize the appearance of the application to match your preference.')}
                                                    </CardDescription>
                    </CardHeader>

                    <CardContent className="pt-6 max-w-2xl">
                        <AppearanceTabs />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Appearance settings',
            href: editAppearance(),
        },
    ],
};
