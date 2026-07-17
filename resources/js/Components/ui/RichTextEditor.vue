<script setup lang="ts">
import { createMentionSuggestion } from '@/utils/mentionSuggestion';
import type { TaskAssignee } from '@/types/task';
import Mention from '@tiptap/extension-mention';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { Bold, Italic, List, ListOrdered } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        placeholder?: string;
        mentionUsers?: TaskAssignee[];
        minHeightClass?: string;
    }>(),
    {
        placeholder: 'Write something…',
        mentionUsers: () => [],
        minHeightClass: 'min-h-32',
    }
);
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const focused = ref(false);
const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Placeholder.configure({ placeholder: props.placeholder }),
        Mention.configure({
            HTMLAttributes: { class: 'mention-chip' },
            suggestion: createMentionSuggestion(() => props.mentionUsers),
        }),
    ],
    editorProps: { attributes: { class: 'tiptap-content' } },
    onUpdate: ({ editor: instance }) =>
        emit('update:modelValue', instance.isEmpty ? '' : instance.getHTML()),
    onFocus: () => {
        focused.value = true;
    },
    onBlur: () => {
        focused.value = false;
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && !editor.value.isFocused && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value, { emitUpdate: false });
        }
    }
);
</script>

<template>
    <div class="tiptap-editor" :class="{ 'tiptap-editor-focused': focused }">
        <div v-if="editor" class="flex items-center gap-1 border-b border-slate-100 px-2 py-1.5">
            <button
                type="button"
                class="tiptap-toolbar-btn"
                :class="{ 'is-active': editor.isActive('bold') }"
                aria-label="Bold"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="h-3.5 w-3.5" />
            </button>
            <button
                type="button"
                class="tiptap-toolbar-btn"
                :class="{ 'is-active': editor.isActive('italic') }"
                aria-label="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="h-3.5 w-3.5" />
            </button>
            <button
                type="button"
                class="tiptap-toolbar-btn"
                :class="{ 'is-active': editor.isActive('bulletList') }"
                aria-label="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="h-3.5 w-3.5" />
            </button>
            <button
                type="button"
                class="tiptap-toolbar-btn"
                :class="{ 'is-active': editor.isActive('orderedList') }"
                aria-label="Ordered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="h-3.5 w-3.5" />
            </button>
            <span class="ml-auto text-[10px] font-medium text-slate-300">Type @ to mention</span>
        </div>
        <EditorContent :editor="editor" class="overflow-y-auto px-3 py-2" :class="minHeightClass" />
    </div>
</template>
