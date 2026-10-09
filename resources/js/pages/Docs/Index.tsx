import { useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { ThumbsUp, ThumbsDown, ArrowLeft, ArrowRight, Book, List, LifeBuoy, ArrowUpRight } from 'lucide-react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';

interface DocsProps {
    page?: {
        id: number;
        title: string;
        slug: string;
        content: Record<string, unknown> | string | null;
        updated_at: string;
    } | null;
    menu: {
        id: number;
        name: string;
        pages: {
            id: number;
            title: string;
            slug: string;
        }[];
    }[];
}

export default function DocsIndex({ page, menu }: DocsProps) {
    const safeContent = page?.content ?? '';

    const editor = useEditor({
        extensions: [StarterKit],
        content: safeContent,
        editable: false,
    });

    useEffect(() => {
        if (editor && safeContent) {
            editor.commands.setContent(safeContent);
        }
    }, [safeContent, editor]);

    if (!page) {
        return (
            <>
                <Head title="Documentation" />
                <div className="bg-background min-h-full">
                    <div className="max-w-[1400px] mx-auto px-4 md:px-8 py-8 lg:py-12">
                        <div className="bg-card border border-border rounded-2xl p-8 md:p-10 shadow-sm mb-8 text-center">
                            <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-foreground mb-4">Belum Ada Dokumentasi</h1>
                            <p className="text-muted-foreground">Saat ini belum ada halaman dokumentasi yang tersedia.</p>
                        </div>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={page.title} />
            <style>{`
                /* Modern Typography & Layout Matching Figma Mockup */
                .markdown-body h1 { display: none; /* Hide H1 since it's now in the layout header */ }
                .markdown-body > p:first-of-type, .markdown-body > p:nth-of-type(2) { display: none; /* Hide intro paras */ }
                
                .markdown-body h2 { font-size: 1.125rem; line-height: 1.75rem; font-weight: 700; letter-spacing: -0.01em; margin-top: 2.5em; margin-bottom: 1em; color: var(--foreground); display: flex; align-items: center; gap: 0.75rem; border-bottom: none; }
                .markdown-body h2::before { content: " "; display: block; width: 0.25rem; height: 1.25rem; background: var(--primary); border-radius: 2px; }
                
                .markdown-body p { margin-bottom: 1.25em; line-height: 1.7; color: var(--foreground); font-size: 0.95rem; }
                
                /* Tables */
                .markdown-body table { width: 100%; margin-bottom: 2em; border-collapse: separate; border-spacing: 0; font-size: 0.875rem; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border); }
                .markdown-body th, .markdown-body td { padding: 1rem 1.25rem; text-align: left; vertical-align: top; border-bottom: 1px solid var(--border); }
                .markdown-body th:not(:last-child), .markdown-body td:not(:last-child) { border-right: 1px solid var(--border); }
                .markdown-body tr:last-child td { border-bottom: none; }
                .markdown-body th { background: var(--muted); font-weight: 600; color: var(--foreground); }
                
                /* Numbered Lists - Workflow Style */
                .markdown-body ol { list-style-type: none; counter-reset: step-counter; padding-left: 0; display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2em; margin-top: 1.5em; }
                .markdown-body ol li { counter-increment: step-counter; position: relative; padding: 1.25rem 1.25rem 1.25rem 4rem; border: 1px solid var(--border); border-radius: var(--radius); background: transparent; }
                .markdown-body ol li::before { content: counter(step-counter); position: absolute; left: 1.25rem; top: 1.25rem; width: 1.75rem; height: 1.75rem; background: var(--foreground); color: var(--background); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.875rem; }
                
                .markdown-body ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1.25em; color: var(--foreground); }
                .markdown-body li { margin-bottom: 0.5em; line-height: 1.6; font-size: 0.95rem; }
                
                .markdown-body code { background: var(--muted); padding: 0.2em 0.4em; border-radius: 4px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.85em; color: var(--foreground); }
                .markdown-body pre { background: var(--muted); padding: 1rem; border-radius: var(--radius); overflow-x: auto; margin-bottom: 1.5em; border: 1px solid var(--border); }
                .markdown-body pre code { background: none; padding: 0; border: none; font-size: 0.875em; }
                
                /* TipTap Focus Outline removal */
                .ProseMirror:focus { outline: none; }
            `}</style>

            <div className="bg-background min-h-full">
                <div className="max-w-[1400px] mx-auto px-4 md:px-8 py-8 lg:py-12">
                    
                    {/* Global Header in a Card */}
                    <div className="bg-card border border-border rounded-2xl p-8 md:p-10 shadow-sm mb-8">
                        <div className="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-3 mb-6">
                            <div className="flex gap-2">
                                <span className="inline-flex items-center rounded-full border border-border px-2.5 py-0.5 text-xs font-semibold bg-muted text-muted-foreground">WLMS Docs</span>
                                <span className="inline-flex items-center rounded-full border border-blue-200 px-2.5 py-0.5 text-xs font-semibold bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800">Standard Operating Procedure</span>
                            </div>
                            <span className="text-sm text-muted-foreground sm:ml-auto flex items-center gap-1.5">
                                <span className="w-1.5 h-1.5 rounded-full bg-green-500"></span> Diperbarui: {new Date(page.updated_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                            </span>
                        </div>
                        <h1 className="text-3xl md:text-4xl lg:text-[40px] font-bold tracking-tight text-foreground mb-4 leading-tight">{page.title}</h1>
                        <p className="text-muted-foreground text-lg max-w-4xl">Dokumen ini adalah panduan standar untuk seluruh anggota tim, Project Manager (PM), dan Developer dalam menggunakan sistem WLMS (Workload Management System).</p>
                        <p className="text-muted-foreground text-lg max-w-4xl mt-2">Tujuan utama dari pedoman ini adalah agar skala pekerjaan bisa diprioritaskan dengan tepat, laporan dapat diukur dengan jelas, dan tidak ada pekerjaan yang saling tumpang tindih.</p>
                    </div>

                    {/* 2-Column Portal Layout */}
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                        
                        {/* Inner Left Sidebar */}
                        <div className="hidden md:flex flex-col col-span-4 lg:col-span-3 space-y-6 sticky top-8">
                            
                            {/* Buku Panduan Block */}
                            <div className="border border-border rounded-xl p-5 bg-card/50">
                                <h3 className="font-semibold flex items-center gap-2 mb-4 text-sm uppercase tracking-wider text-foreground">
                                    <Book className="w-4 h-4 text-muted-foreground" /> Buku Panduan WLMS
                                </h3>
                                <div className="space-y-1">
                                    {menu.map((category) => (
                                        <div key={category.id}>
                                            <div className="text-xs font-semibold text-muted-foreground mb-2 mt-4 px-2">{category.name}</div>
                                            {category.pages.map((p) => {
                                                const isActive = p.slug === page.slug;
                                                return (
                                                    <Link
                                                        key={p.id}
                                                        href={`/docs/${p.slug}`}
                                                        className={`block px-2 py-1.5 text-sm rounded-md transition-colors ${
                                                            isActive 
                                                                ? 'font-medium text-foreground bg-muted/50 border-l-2 border-primary' 
                                                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                                        }`}
                                                    >
                                                        {p.title}
                                                    </Link>
                                                );
                                            })}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* TOC Block */}
                            <div className="border border-border rounded-xl p-5 bg-card/50">
                                <h3 className="font-semibold flex items-center gap-2 mb-4 text-sm uppercase tracking-wider text-foreground">
                                    <List className="w-4 h-4 text-muted-foreground" /> Daftar Isi Halaman
                                </h3>
                                <div className="space-y-2">
                                    <a href="#" className="block text-sm text-foreground hover:text-primary transition-colors">1. Tipe Tiket & Hirarki</a>
                                    <a href="#" className="block text-sm text-muted-foreground hover:text-foreground transition-colors">2. Skala Prioritas & SLA</a>
                                    <a href="#" className="block text-sm text-muted-foreground hover:text-foreground transition-colors">3. Skenario Modul Keranjang</a>
                                    <a href="#" className="block text-sm text-muted-foreground hover:text-foreground transition-colors">4. Rangkuman Siklus Kerja</a>
                                </div>
                            </div>

                            {/* Support Card */}
                            <div className="border border-blue-200 bg-blue-50/50 dark:border-blue-900/50 dark:bg-blue-900/10 rounded-xl p-5">
                                <h3 className="font-semibold text-blue-900 dark:text-blue-400 flex items-center gap-2 mb-2 text-sm">
                                    <LifeBuoy className="w-4 h-4" /> Butuh Bantuan Tim?
                                </h3>
                                <p className="text-xs text-blue-700/80 dark:text-blue-300/80 mb-3">Jika Anda menemukan masalah sistem yang tidak tercantum, hubungi Tim IT Anda.</p>
                                <Button variant="outline" size="sm" className="w-full text-blue-700 border-blue-200 hover:bg-blue-100 dark:text-blue-300 dark:border-blue-800 dark:hover:bg-blue-900">
                                    Buka Tiket Bantuan <ArrowUpRight className="w-3 h-3 ml-1" />
                                </Button>
                            </div>
                        </div>
                        
                        {/* Main Reading Canvas */}
                        <div className="col-span-1 md:col-span-8 lg:col-span-9">
                            <div className="bg-card border border-border/80 rounded-2xl p-6 md:p-10 shadow-sm relative">
                                                                                                                                <div className="markdown-body max-w-none"><EditorContent editor={editor} /></div>

                                <hr className="my-10 border-border" />

                                {/* Feedback Widget */}
                                <div className="flex flex-col sm:flex-row items-center justify-between bg-muted/20 border border-border rounded-xl p-6 mb-8 gap-4">
                                    <div className="text-sm font-medium text-foreground">
                                        Apakah dokumentasi panduan ini membantu Anda?
                                        <p className="text-xs text-muted-foreground mt-1 font-normal">Bantu kami meningkatkan kualitas dokumentasi operasional WLMS.</p>
                                    </div>
                                    <div className="flex gap-2 w-full sm:w-auto">
                                        <Button variant="outline" size="sm" className="w-full sm:w-auto bg-background">
                                            <ThumbsUp className="w-4 h-4 mr-2" /> Ya, sangat jelas
                                        </Button>
                                        <Button variant="outline" size="sm" className="w-full sm:w-auto bg-background">
                                            <ThumbsDown className="w-4 h-4 mr-2" /> Kurang lengkap
                                        </Button>
                                    </div>
                                </div>

                                {/* Pagination */}
                                <div className="flex justify-between items-center text-sm pt-4 border-t border-border/50">
                                    <a href="#" className="flex flex-col text-muted-foreground hover:text-foreground transition-colors group">
                                        <span className="text-xs mb-1 group-hover:text-foreground">Sebelumnya</span>
                                        <span className="font-medium flex items-center gap-1"><ArrowLeft className="w-4 h-4" /> Konfigurasi Akun</span>
                                    </a>
                                    <a href="#" className="flex flex-col items-end text-muted-foreground hover:text-foreground transition-colors group">
                                        <span className="text-xs mb-1 group-hover:text-foreground">Selanjutnya</span>
                                        <span className="font-medium flex items-center gap-1">Alur Sprint & Backlog <ArrowRight className="w-4 h-4" /></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </>
    );
}

DocsIndex.layout = (page: React.ReactNode) => {
    return (
        <AppLayout breadcrumbs={[{ title: 'Documentation', href: '/docs' }]}>
            {page}
        </AppLayout>
    );
};
