import { Head, Link } from '@inertiajs/react';
import { Hammer, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';


export default function ComingSoon({ title }: { title?: string }) {
    const pageTitle = title || 'Coming Soon';
    
    return (
        <>
            <Head title={pageTitle} />
            <div className="flex h-full flex-1 flex-col items-center justify-center text-center px-4 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card">
                <div className="bg-primary/10 p-5 rounded-full mb-6">
                    <Hammer className="w-12 h-12 text-primary" />
                </div>
                <h1 className="text-3xl font-bold mb-3">Halaman {pageTitle} Sedang Dibangun</h1>
                <p className="text-muted-foreground mb-8 max-w-lg text-lg">
                    Fitur ini masih dalam tahap pengembangan aktif oleh tim engineering. 
                    Silakan kembali lagi nanti saat *sprint* berikutnya dirilis!
                </p>
                <Button asChild size="lg">
                    <Link href="/dashboard">
                        <ArrowLeft className="w-4 h-4 mr-2" />
                        Kembali ke Dashboard
                    </Link>
                </Button>
            </div>
        </>
    );
}

ComingSoon.layout = {
    breadcrumbs: [
        {
            title: 'Dalam Pengembangan',
            href: '#',
        },
    ],
};

