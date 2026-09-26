import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { index as projectsIndex } from '@/routes/projects';
import { ArrowRight, LayoutList } from 'lucide-react';

export default function IssuesIndex() {
    return (
        <>
            <Head title="Global Issues" />
            
            <div className="flex h-full w-full flex-col gap-6 p-6 items-center justify-center min-h-[60vh]">
                <Card className="w-full max-w-md text-center border-dashed border-2">
                    <CardHeader>
                        <div className="flex justify-center mb-4">
                            <div className="p-3 bg-primary/10 rounded-full">
                                <LayoutList className="w-8 h-8 text-primary" />
                            </div>
                        </div>
                        <CardTitle className="text-2xl">Project-Based Ticketing</CardTitle>
                        <CardDescription className="mt-2 text-base">
                            Sistem tiket (Issues) dikelola secara spesifik di dalam sebuah Project. 
                            Silakan masuk ke direktori Project Anda terlebih dahulu untuk mengelola tiket.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button 
                            onClick={() => router.visit(projectsIndex().url)} 
                            className="w-full"
                            size="lg"
                        >
                            Ke Direktori Projects
                            <ArrowRight className="ml-2 w-4 h-4" />
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

import { index as issuesIndex } from '@/routes/issues';

IssuesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Issues',
            href: issuesIndex(),
        },
    ],
};
