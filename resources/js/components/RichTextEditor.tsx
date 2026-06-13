import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import axios from 'axios';
import { ClipboardEvent, DragEvent, useCallback, useState } from 'react';
import {
    Bold,
    Italic,
    List,
    ListOrdered,
    AlignLeft,
    AlignCenter,
    AlignRight,
    AlignJustify,
    Link as LinkIcon,
    Quote,
    Heading1,
    Heading2,
    Heading3,
    Strikethrough,
    Code,
    Image as ImageIcon,
} from 'lucide-react';
import { Toggle } from './ui/toggle';
import { Separator } from './ui/separator';
import MediaLibraryDialog from './MediaLibraryDialog';
import LinkDialog from './LinkDialog';
import { sanitizeUrl } from '@/utils/urlValidator';
import { toast } from 'sonner';

interface RichTextEditorProps {
    content: string;
    onChange: (content: string) => void;
}

export default function RichTextEditor({ content, onChange }: RichTextEditorProps) {
    const [isMediaDialogOpen, setIsMediaDialogOpen] = useState(false);
    const [isLinkDialogOpen, setIsLinkDialogOpen] = useState(false);
    const editor = useEditor({
        extensions: [
            StarterKit,
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Link.configure({
                openOnClick: false,
            }),
            Image.configure({
                allowBase64: false,
            }),
        ],
        content,
        onUpdate: ({ editor }) => {
            onChange(editor.getHTML());
        },
    });

    const uploadAndInsertImage = useCallback(
        async (file: File, pos?: number) => {
            if (!editor) {
                return;
            }
            const formData = new FormData();
            formData.append('files[]', file);
            const response = await axios.post<
                { id: number; url: string; name: string; variant_urls: Record<string, string> }[]
            >(route('admin.media.store'), formData, {
                headers: {
                    Accept: 'application/json',
                },
            });

            const uploaded = response.data?.[0];
            if (!uploaded?.url) {
                throw new Error('Upload berhasil, tapi URL tidak tersedia');
            }

            const url = sanitizeUrl(uploaded.url);
            if (pos) {
                editor.chain().focus().setTextSelection(pos).setImage({ src: url }).run();
                return;
            }
            editor.chain().focus().setImage({ src: url }).run();
        },
        [editor]
    );

    const handlePaste = useCallback(
        async (e: ClipboardEvent<HTMLDivElement>) => {
            const items = e.clipboardData?.items;
            if (!items || items.length === 0) {
                return;
            }

            const imageItem = Array.from(items).find(
                (it) => it.kind === 'file' && it.type.startsWith('image/')
            );
            const file = imageItem?.getAsFile();
            if (!file) {
                return;
            }

            e.preventDefault();
            try {
                await uploadAndInsertImage(file);
            } catch (err) {
                const message = err instanceof Error ? err.message : 'Gagal upload gambar';
                toast.error(message);
            }
        },
        [uploadAndInsertImage]
    );

    const handleDrop = useCallback(
        async (e: DragEvent<HTMLDivElement>) => {
            if (!editor) {
                return;
            }
            const file = e.dataTransfer?.files?.[0];
            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            e.preventDefault();

            const coords = editor.view.posAtCoords({ left: e.clientX, top: e.clientY });
            const pos = coords?.pos;

            try {
                await uploadAndInsertImage(file, pos);
            } catch (err) {
                const message = err instanceof Error ? err.message : 'Gagal upload gambar';
                toast.error(message);
            }
        },
        [uploadAndInsertImage, editor]
    );

    if (!editor) {
        return null;
    }

    const activeLinkHref = editor.getAttributes('link')?.href as string | undefined;

    const toolbarItems = [
        {
            icon: <Heading1 className="h-4 w-4" />,
            title: 'Heading 1',
            action: () => editor.chain().focus().toggleHeading({ level: 1 }).run(),
            isActive: () => editor.isActive('heading', { level: 1 }),
        },
        {
            icon: <Heading2 className="h-4 w-4" />,
            title: 'Heading 2',
            action: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
            isActive: () => editor.isActive('heading', { level: 2 }),
        },
        {
            icon: <Heading3 className="h-4 w-4" />,
            title: 'Heading 3',
            action: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
            isActive: () => editor.isActive('heading', { level: 3 }),
        },
        { type: 'separator' },
        {
            icon: <Bold className="h-4 w-4" />,
            title: 'Bold',
            action: () => editor.chain().focus().toggleBold().run(),
            isActive: () => editor.isActive('bold'),
        },
        {
            icon: <Italic className="h-4 w-4" />,
            title: 'Italic',
            action: () => editor.chain().focus().toggleItalic().run(),
            isActive: () => editor.isActive('italic'),
        },
        {
            icon: <Strikethrough className="h-4 w-4" />,
            title: 'Strike',
            action: () => editor.chain().focus().toggleStrike().run(),
            isActive: () => editor.isActive('strike'),
        },
        { type: 'separator' },
        {
            icon: <List className="h-4 w-4" />,
            title: 'Bullet List',
            action: () => editor.chain().focus().toggleBulletList().run(),
            isActive: () => editor.isActive('bulletList'),
        },
        {
            icon: <ListOrdered className="h-4 w-4" />,
            title: 'Ordered List',
            action: () => editor.chain().focus().toggleOrderedList().run(),
            isActive: () => editor.isActive('orderedList'),
        },
        { type: 'separator' },
        {
            icon: <AlignLeft className="h-4 w-4" />,
            title: 'Align Left',
            action: () => editor?.chain().focus().setTextAlign('left').run(),
            isActive: () => editor?.isActive({ textAlign: 'left' }) ?? false,
        },
        {
            icon: <AlignCenter className="h-4 w-4" />,
            title: 'Align Center',
            action: () => editor?.chain().focus().setTextAlign('center').run(),
            isActive: () => editor?.isActive({ textAlign: 'center' }) ?? false,
        },
        {
            icon: <AlignRight className="h-4 w-4" />,
            title: 'Align Right',
            action: () => editor?.chain().focus().setTextAlign('right').run(),
            isActive: () => editor?.isActive({ textAlign: 'right' }) ?? false,
        },
        {
            icon: <AlignJustify className="h-4 w-4" />,
            title: 'Align Justify',
            action: () => editor?.chain().focus().setTextAlign('justify').run(),
            isActive: () => editor?.isActive({ textAlign: 'justify' }) ?? false,
        },
        { type: 'separator' },
        {
            icon: <LinkIcon className="h-4 w-4" />,
            title: 'Link',
            action: () => setIsLinkDialogOpen(true),
            isActive: () => editor?.isActive('link') ?? false,
        },
        {
            icon: <ImageIcon className="h-4 w-4" />,
            title: 'Image',
            action: () => setIsMediaDialogOpen(true),
            isActive: () => false,
        },
        {
            icon: <Quote className="h-4 w-4" />,
            title: 'Blockquote',
            action: () => editor.chain().focus().toggleBlockquote().run(),
            isActive: () => editor.isActive('blockquote'),
        },
        {
            icon: <Code className="h-4 w-4" />,
            title: 'Code',
            action: () => editor.chain().focus().toggleCode().run(),
            isActive: () => editor.isActive('code'),
        },
    ];

    return (
        <div className="border rounded-lg overflow-hidden">
            <MediaLibraryDialog
                open={isMediaDialogOpen}
                onOpenChange={setIsMediaDialogOpen}
                onSelectUrl={(url) => {
                    editor.chain().focus().setImage({ src: sanitizeUrl(url) }).run();
                }}
                type="image"
                enableUpload={true}
            />
            <LinkDialog
                open={isLinkDialogOpen}
                onOpenChange={setIsLinkDialogOpen}
                initialHref={activeLinkHref}
                onSubmit={(href) => {
                    editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
                }}
                onUnset={() => {
                    editor.chain().focus().unsetLink().run();
                }}
            />
            <div className="border-b p-2 flex flex-wrap gap-1 items-center">
                {toolbarItems.map((item, index) => {
                    if (item.type === 'separator') {
                        return <Separator orientation="vertical" className="h-6 " key={index} />;
                    }

                    return (
                        <Toggle
                            key={item.title}
                            pressed={item.isActive?.() ?? false}
                            onPressedChange={() => item.action?.()}
                            title={item.title}
                            className="h-8 w-8 p-0 data-[state=on]:bg-gray-700 data-[state=on]:text-gray-50 data-[state=off]:text-gray-400 data-[state=off]:hover:bg-gray-700 data-[state=off]:hover:text-gray-50 transition-colors rounded-md flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-gray-500"
                        >
                            {item.icon}
                        </Toggle>
                    );
                })}
            </div>

            <EditorContent
                editor={editor}
                onPaste={handlePaste}
                onDrop={handleDrop}
                onDragOver={(e) => {
                    if (e.dataTransfer?.files?.[0]?.type?.startsWith('image/')) {
                        e.preventDefault();
                    }
                }}
                className="prose dark:prose-invert max-w-none p-4 min-h-[300px] focus:outline-none [&_.tiptap]:outline-none [&_.tiptap]:min-h-[300px]"
            />
        </div>
    );
}
