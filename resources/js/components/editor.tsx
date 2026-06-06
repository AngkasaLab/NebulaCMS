import { useEditor, EditorContent, Editor as TiptapEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import axios from 'axios';
import { useCallback, useState } from 'react';
import { Toggle } from '@/components/ui/toggle';
import MediaLibraryDialog from '@/components/MediaLibraryDialog';
import LinkDialog from '@/components/LinkDialog';
import { sanitizeUrl } from '@/utils/urlValidator';
import { toast } from 'sonner';
import {
  Bold,
  Image as ImageIcon,
  Italic,
  Link2,
  List,
  ListOrdered,
  Quote,
  Redo,
  Strikethrough,
  Undo,
} from 'lucide-react';

interface EditorProps {
  value: string;
  onChange: (value: string) => void;
}

interface MenuBarProps {
  editor: TiptapEditor | null;
  onOpenMedia: () => void;
  onOpenLink: () => void;
}

const MenuBar = ({ editor, onOpenMedia, onOpenLink }: MenuBarProps) => {
  if (!editor) {
    return null;
  }

  return (
    <div className="border-b p-2 flex flex-wrap gap-2">
      <Toggle
        size="sm"
        pressed={editor.isActive('bold')}
        onPressedChange={() => editor.chain().focus().toggleBold().run()}
      >
        <Bold className="h-4 w-4" />
      </Toggle>
      <Toggle
        size="sm"
        pressed={editor.isActive('italic')}
        onPressedChange={() => editor.chain().focus().toggleItalic().run()}
      >
        <Italic className="h-4 w-4" />
      </Toggle>
      <Toggle
        size="sm"
        pressed={editor.isActive('strike')}
        onPressedChange={() => editor.chain().focus().toggleStrike().run()}
      >
        <Strikethrough className="h-4 w-4" />
      </Toggle>
      <Toggle
        size="sm"
        pressed={editor.isActive('bulletList')}
        onPressedChange={() => editor.chain().focus().toggleBulletList().run()}
      >
        <List className="h-4 w-4" />
      </Toggle>
      <Toggle
        size="sm"
        pressed={editor.isActive('orderedList')}
        onPressedChange={() => editor.chain().focus().toggleOrderedList().run()}
      >
        <ListOrdered className="h-4 w-4" />
      </Toggle>
      <Toggle
        size="sm"
        pressed={editor.isActive('blockquote')}
        onPressedChange={() => editor.chain().focus().toggleBlockquote().run()}
      >
        <Quote className="h-4 w-4" />
      </Toggle>
      <Toggle size="sm" pressed={editor.isActive('link')} onPressedChange={onOpenLink}>
        <Link2 className="h-4 w-4" />
      </Toggle>
      <Toggle size="sm" onPressedChange={onOpenMedia}>
        <ImageIcon className="h-4 w-4" />
      </Toggle>
      <Toggle
        size="sm"
        onPressedChange={() => editor.chain().focus().undo().run()}
        disabled={!editor.can().undo()}
      >
        <Undo className="h-4 w-4" />
      </Toggle>
      <Toggle
        size="sm"
        onPressedChange={() => editor.chain().focus().redo().run()}
        disabled={!editor.can().redo()}
      >
        <Redo className="h-4 w-4" />
      </Toggle>
    </div>
  );
};

export function Editor({ value, onChange }: EditorProps) {
  const [isMediaDialogOpen, setIsMediaDialogOpen] = useState(false);
  const [isLinkDialogOpen, setIsLinkDialogOpen] = useState(false);
  const editor = useEditor({
    extensions: [
      StarterKit,
      Link.configure({
        openOnClick: false,
      }),
      Image.configure({
        allowBase64: false,
      }),
    ],
    content: value,
    onUpdate: useCallback(
      ({ editor }: { editor: TiptapEditor }) => {
        const html = editor.getHTML();
        onChange(html);
      },
      [onChange]
    ),
  });

  const uploadAndInsertImage = useCallback(
    async (file: File, pos?: number) => {
      if (!editor) {
        return;
      }
      const formData = new FormData();
      formData.append('files[]', file);
      const response = await axios.post<{ id: number; url: string }[]>(
        route('admin.media.store'),
        formData,
        {
          headers: {
            Accept: 'application/json',
          },
        }
      );
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

  return (
    <div className="border rounded-md">
      <MediaLibraryDialog
        open={isMediaDialogOpen}
        onOpenChange={setIsMediaDialogOpen}
        onSelectUrl={(url) => {
          if (!editor) {
            return;
          }
          editor.chain().focus().setImage({ src: sanitizeUrl(url) }).run();
        }}
        type="image"
        enableUpload={true}
      />
      <LinkDialog
        open={isLinkDialogOpen}
        onOpenChange={setIsLinkDialogOpen}
        initialHref={(editor?.getAttributes('link')?.href as string | undefined) ?? undefined}
        onSubmit={(href) => {
          if (!editor) {
            return;
          }
          editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
        }}
        onUnset={() => {
          if (!editor) {
            return;
          }
          editor.chain().focus().unsetLink().run();
        }}
      />
      <MenuBar
        editor={editor}
        onOpenMedia={() => setIsMediaDialogOpen(true)}
        onOpenLink={() => setIsLinkDialogOpen(true)}
      />
      <EditorContent
        editor={editor}
        onPaste={async (e) => {
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
        }}
        onDrop={async (e) => {
          const file = e.dataTransfer?.files?.[0];
          if (!file || !file.type.startsWith('image/')) {
            return;
          }

          e.preventDefault();
          if (!editor) {
            return;
          }
          const coords = editor.view.posAtCoords({ left: e.clientX, top: e.clientY });
          const pos = coords?.pos;
          try {
            await uploadAndInsertImage(file, pos);
          } catch (err) {
            const message = err instanceof Error ? err.message : 'Gagal upload gambar';
            toast.error(message);
          }
        }}
        onDragOver={(e) => {
          if (e.dataTransfer?.files?.[0]?.type?.startsWith('image/')) {
            e.preventDefault();
          }
        }}
        className="prose prose-sm max-w-none p-4 focus:outline-none"
      />
    </div>
  );
}
