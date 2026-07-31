import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';

type Props = {
    value: string;
    onChange: (value: string) => void;
};

export default function RichTextEditor({ value, onChange }: Props) {
    const editor = useEditor({
        extensions: [StarterKit],
        content: value,
        onUpdate: ({ editor }) => {
            onChange(editor.getHTML());
        },
        editorProps: {
            attributes: {
                class: 'min-h-[150px] rounded-md border p-2 prose prose-sm max-w-none focus:outline-none',
            },
        },
    });

    if (!editor) {
        return null;
    }

    return (
        <div className="flex flex-col gap-2">
            <div className="flex gap-2">
                <button
                    type="button"
                    onClick={() => editor.chain().focus().toggleBold().run()}
                    className={`rounded px-2 py-1 text-sm ${editor.isActive('bold') ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}
                >
                    Kalın
                </button>
                <button
                    type="button"
                    onClick={() => editor.chain().focus().toggleItalic().run()}
                    className={`rounded px-2 py-1 text-sm ${editor.isActive('italic') ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}
                >
                    İtalik
                </button>
                <button
                    type="button"
                    onClick={() => editor.chain().focus().toggleBulletList().run()}
                    className={`rounded px-2 py-1 text-sm ${editor.isActive('bulletList') ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}
                >
                    Liste
                </button>
            </div>

            <EditorContent editor={editor} />
        </div>
    );
}
