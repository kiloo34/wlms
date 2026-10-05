import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { FileText, Edit2 } from 'lucide-react';

interface DocPage {
    id: string;
    title: string;
    slug: string;
    is_published: boolean;
    category?: { name: string };
}

interface Props {
    pages: DocPage[];
}

export default function AdminDocsIndex({ pages }: Props) {
    return (
        <>
            <Head title="Knowledge Base Admin" />
            <div className="container mx-auto py-10 max-w-5xl">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Dokumentasi (CMS)</h1>
                        <p className="text-muted-foreground mt-2">Kelola halaman dokumentasi dan buku panduan WLMS.</p>
                    </div>
                    <Button disabled>+ Tambah Halaman</Button>
                </div>

                <div className="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm text-left">
                        <thead className="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th className="px-6 py-4 font-medium">Judul Halaman</th>
                                <th className="px-6 py-4 font-medium">Kategori</th>
                                <th className="px-6 py-4 font-medium">Status</th>
                                <th className="px-6 py-4 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {pages.map((page) => (
                                <tr key={page.id} className="hover:bg-muted/30 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <FileText className="w-4 h-4 text-primary" />
                                            <span className="font-medium text-foreground">{page.title}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-muted-foreground">
                                        {page.category?.name || '-'}
                                    </td>
                                    <td className="px-6 py-4">
                                        {page.is_published ? (
                                            <span className="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400">Published</span>
                                        ) : (
                                            <span className="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-white/5 dark:text-gray-400">Draft</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <Link href={`/admin/docs/${page.id}/edit`}>
                                            <Button variant="ghost" size="sm" className="gap-2">
                                                <Edit2 className="w-4 h-4" /> Edit
                                            </Button>
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {pages.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-6 py-8 text-center text-muted-foreground">
                                        Belum ada dokumen.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}


AdminDocsIndex.layout = {
    breadcrumbs: [
        { title: 'Dokumentasi', href: '/admin/docs' },
    ],
};
