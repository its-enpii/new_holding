<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import Placeholder from '@tiptap/extension-placeholder';
import { Table, TableRow, TableCell, TableHeader } from '@tiptap/extension-table';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Tulis konten…' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue || '',
    editable: !props.disabled,
    extensions: [
        StarterKit.configure({ heading: false }),
        Underline,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Placeholder.configure({ placeholder: props.placeholder }),
        Table.configure({ resizable: false }),
        TableRow,
        TableHeader,
        TableCell,
    ],
    onUpdate: ({ editor: ed }) => {
        emit('update:modelValue', ed.getHTML());
    },
    editorProps: {
        attributes: {
            class: 'rich-editor__content',
        },
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;
        const current = editor.value.getHTML();
        if (value !== current) {
            editor.value.commands.setContent(value || '', false);
        }
    },
);

watch(
    () => props.disabled,
    (disabled) => {
        editor.value?.setEditable(!disabled);
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function run(fn) {
    if (!editor.value || props.disabled) return;
    fn(editor.value.chain().focus());
}

function insertSignatureTable() {
    run((chain) =>
        chain
            .insertTable({ rows: 1, cols: 3, withHeaderRow: false })
            .run(),
    );
}
</script>

<template>
    <div class="rich-editor" :class="{ 'is-disabled': disabled }">
        <div v-if="editor" class="rich-editor__toolbar" role="toolbar" aria-label="Format teks">
            <button type="button" class="rich-editor__btn" :class="{ 'is-active': editor.isActive('bold') }" title="Tebal" :disabled="disabled" @click="run((c) => c.toggleBold().run())">
                <AppIcon name="format_bold" class="text-lg" />
            </button>
            <button type="button" class="rich-editor__btn" :class="{ 'is-active': editor.isActive('italic') }" title="Miring" :disabled="disabled" @click="run((c) => c.toggleItalic().run())">
                <AppIcon name="format_italic" class="text-lg" />
            </button>
            <button type="button" class="rich-editor__btn" :class="{ 'is-active': editor.isActive('underline') }" title="Garis bawah" :disabled="disabled" @click="run((c) => c.toggleUnderline().run())">
                <AppIcon name="format_underlined" class="text-lg" />
            </button>
            <span class="rich-editor__sep" />
            <button type="button" class="rich-editor__btn" :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }" title="Rata kiri" :disabled="disabled" @click="run((c) => c.setTextAlign('left').run())">
                <AppIcon name="format_align_left" class="text-lg" />
            </button>
            <button type="button" class="rich-editor__btn" :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }" title="Rata tengah" :disabled="disabled" @click="run((c) => c.setTextAlign('center').run())">
                <AppIcon name="format_align_center" class="text-lg" />
            </button>
            <button type="button" class="rich-editor__btn" :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }" title="Rata kanan" :disabled="disabled" @click="run((c) => c.setTextAlign('right').run())">
                <AppIcon name="format_align_right" class="text-lg" />
            </button>
            <span class="rich-editor__sep" />
            <button type="button" class="rich-editor__btn" :class="{ 'is-active': editor.isActive('bulletList') }" title="Daftar" :disabled="disabled" @click="run((c) => c.toggleBulletList().run())">
                <AppIcon name="format_list_bulleted" class="text-lg" />
            </button>
            <button type="button" class="rich-editor__btn" :class="{ 'is-active': editor.isActive('orderedList') }" title="Daftar bernomor" :disabled="disabled" @click="run((c) => c.toggleOrderedList().run())">
                <AppIcon name="format_list_numbered" class="text-lg" />
            </button>
            <span class="rich-editor__sep" />
            <button type="button" class="rich-editor__btn" title="Sisipkan tabel tanda tangan (1×3)" :disabled="disabled" @click="insertSignatureTable">
                <AppIcon name="table" class="text-lg" />
            </button>
            <button type="button" class="rich-editor__btn" title="Tambah baris" :disabled="disabled || !editor.can().addRowAfter()" @click="run((c) => c.addRowAfter().run())">
                <AppIcon name="add" class="text-lg" />
            </button>
            <button type="button" class="rich-editor__btn" title="Hapus tabel" :disabled="disabled || !editor.can().deleteTable()" @click="run((c) => c.deleteTable().run())">
                <AppIcon name="delete" class="text-lg" />
            </button>
            <span class="rich-editor__sep" />
            <button type="button" class="rich-editor__btn" title="Undo" :disabled="disabled || !editor.can().undo()" @click="run((c) => c.undo().run())">
                <AppIcon name="undo" class="text-lg" />
            </button>
            <button type="button" class="rich-editor__btn" title="Redo" :disabled="disabled || !editor.can().redo()" @click="run((c) => c.redo().run())">
                <AppIcon name="redo" class="text-lg" />
            </button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
