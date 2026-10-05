import { useEffect } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import { ArrowLeft, ExternalLink } from 'lucide-react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';

interface EditProps {
    page: {
        id: number;
        title: string;
        slug: string;
        content: Record<string, unknown> | string | null;
    };
}

export default function DocsEdit({ page }: EditProps) {
    const editor = useEditor({
        extensions: [StarterKit],
        content: page.content,
    });

    useEffect(() => {
        if (editor && page.content) {
            // Only set content if the editor's current JSON is different (basic check to avoid cursor jump)
            // But for Reader (editable: false) it's safe to just set it.
            if (editor.isEditable) {
                // In edit mode, usually Inertia props don't change unprompted, but just in case:
                // We won't force setContent on every render to avoid resetting cursor.
            } else {
                editor.commands.setContent(page.content);
            }
        }
    }, [page.content, editor]);

    const handleSave = () => {
        if (!editor) return;
        const toastId = toast.loading('Menyimpan dokumen...');
        router.put(`/admin/docs/${page.id}`, {
            content: editor.getJSON(),
        }, {
            onSuccess: () => {
                toast.success('Berhasil', { 
                    id: toastId,
                    description: 'Dokumen telah berhasil disimpan.'
                });
            },
            onError: () => {
                toast.error('Gagal', { 
                    id: toastId,
                    description: 'Gagal menyimpan dokumen. Silakan coba lagi.'
                });
            }
        });
    };

    return (
        <>
            <Head title={`Edit Docs: ${page.title}`} />
            <style>{`
                /* TipTap styling for Admin Editor */
                .ProseMirror:focus { outline: none; }
                .ProseMirror { min-height: 400px; padding: 1rem; }
            `}</style>
            
            <div className="max-w-4xl mx-auto py-8 px-4">
                <div className="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/docs">
                            <Button variant="outline" size="icon" title="Kembali ke Daftar">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold">Edit Dokumen</h1>
                            <p className="text-muted-foreground">{page.title}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href={`/docs/${page.slug}`}>
                            <Button variant="outline" className="gap-2" title="Lihat Hasil">
                                <ExternalLink className="w-4 h-4" /> Lihat Artikel
                            </Button>
                        </Link>
                        <Button onClick={handleSave}>Simpan</Button>
                    </div>
                </div>
                
                <div className="border border-border rounded-md shadow-sm bg-card">
                    {editor && (
                        <div className="flex flex-wrap gap-2 border-b border-border p-4 bg-muted/20 rounded-t-md">
                            <button onClick={() => editor.chain().focus().toggleBold().run()} disabled={!editor.can().chain().focus().toggleBold().run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('bold') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>Bold</button>
                            <button onClick={() => editor.chain().focus().toggleItalic().run()} disabled={!editor.can().chain().focus().toggleItalic().run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('italic') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>Italic</button>
                            <button onClick={() => editor.chain().focus().toggleStrike().run()} disabled={!editor.can().chain().focus().toggleStrike().run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('strike') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>Strike</button>
                            <button onClick={() => editor.chain().focus().toggleCode().run()} disabled={!editor.can().chain().focus().toggleCode().run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('code') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>Code</button>
                            <button onClick={() => editor.chain().focus().unsetAllMarks().run()} className="px-3 py-1.5 text-sm font-medium rounded-md transition-colors bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Clear marks</button>
                            <button onClick={() => editor.chain().focus().clearNodes().run()} className="px-3 py-1.5 text-sm font-medium rounded-md transition-colors bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Clear nodes</button>
                            <button onClick={() => editor.chain().focus().setParagraph().run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('paragraph') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>Paragraph</button>
                            <button onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('heading', { level: 1 }) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>H1</button>
                            <button onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('heading', { level: 2 }) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>H2</button>
                            <button onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('heading', { level: 3 }) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>H3</button>
                            <button onClick={() => editor.chain().focus().toggleHeading({ level: 4 }).run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('heading', { level: 4 }) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>H4</button>
                            <button onClick={() => editor.chain().focus().toggleHeading({ level: 5 }).run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('heading', { level: 5 }) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>H5</button>
                            <button onClick={() => editor.chain().focus().toggleHeading({ level: 6 }).run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('heading', { level: 6 }) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>H6</button>
                            <button onClick={() => editor.chain().focus().toggleBulletList().run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('bulletList') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>Bullet list</button>
                            <button onClick={() => editor.chain().focus().toggleOrderedList().run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('orderedList') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>Ordered list</button>
                            <button onClick={() => editor.chain().focus().toggleCodeBlock().run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('codeBlock') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>Code block</button>
                            <button onClick={() => editor.chain().focus().toggleBlockquote().run()} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${editor.isActive('blockquote') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`}>Blockquote</button>
                            <button onClick={() => editor.chain().focus().setHorizontalRule().run()} className="px-3 py-1.5 text-sm font-medium rounded-md transition-colors bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Horizontal rule</button>
                            <button onClick={() => editor.chain().focus().setHardBreak().run()} className="px-3 py-1.5 text-sm font-medium rounded-md transition-colors bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Hard break</button>
                            <button onClick={() => editor.chain().focus().undo().run()} disabled={!editor.can().chain().focus().undo().run()} className="px-3 py-1.5 text-sm font-medium rounded-md transition-colors bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Undo</button>
                            <button onClick={() => editor.chain().focus().redo().run()} disabled={!editor.can().chain().focus().redo().run()} className="px-3 py-1.5 text-sm font-medium rounded-md transition-colors bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Redo</button>
                        </div>
                    )}
                    
                    <div className="markdown-body">
                        <EditorContent editor={editor} />
                    </div>
                </div>
            </div>
        </>
    );
}

DocsEdit.layout = {
    breadcrumbs: [
        { title: 'Dokumentasi', href: '/admin/docs' },
        { title: 'Edit', href: '#' },
    ],
};
