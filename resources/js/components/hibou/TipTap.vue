<script setup>
import StarterKit from '@tiptap/starter-kit';
import { Editor, EditorContent } from '@tiptap/vue-3';
import { Bold, Heading1, Heading2, Heading3, Italic, List, ListOrdered, Redo, Strikethrough, TextQuote, Undo } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const editor = ref(Editor | null);

const model = defineModel({
    type: String,
    required: true,
});

watch(
    () => model.value,
    (value) => {
        if (!editor.value) return;

        const isSame = editor.value.getHTML() === value;
        if (isSame) return;

        editor.value.commands.setContent(value);
    },
);

onMounted(() => {
    editor.value = new Editor({
        extensions: [StarterKit],
        content: model.value,
        onUpdate: ({ editor }) => {
            model.value = editor.getHTML();
        },
    });
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div
        v-if="editor"
        class="tiptap-container w-full min-w-0 rounded-md border border-input bg-transparent text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm"
    >
        <div class="tiptap-control-group">
            <div class="tiptap-button-group">
                <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().chain().focus().undo().run()">
                    <Undo />
                </button>
                <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().chain().focus().redo().run()">
                    <Redo />
                </button>
                <div class="tiptap-separator"></div>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleBold().run()"
                    :disabled="!editor.can().chain().focus().toggleBold().run()"
                    :class="{ 'is-active': editor.isActive('bold') }"
                >
                    <Bold />
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleItalic().run()"
                    :disabled="!editor.can().chain().focus().toggleItalic().run()"
                    :class="{ 'is-active': editor.isActive('italic') }"
                >
                    <Italic />
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleStrike().run()"
                    :disabled="!editor.can().chain().focus().toggleStrike().run()"
                    :class="{ 'is-active': editor.isActive('strike') }"
                >
                    <Strikethrough />
                </button>
                <div class="tiptap-separator"></div>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
                >
                    <Heading1 />
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
                >
                    <Heading2 />
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
                >
                    <Heading3 />
                </button>
                <div class="tiptap-separator"></div>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleBulletList().run()"
                    :class="{ 'is-active': editor.isActive('bulletList') }"
                >
                    <List />
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleOrderedList().run()"
                    :class="{ 'is-active': editor.isActive('orderedList') }"
                >
                    <ListOrdered />
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleBlockquote().run()"
                    :class="{ 'is-active': editor.isActive('blockquote') }"
                >
                    <TextQuote />
                </button>
            </div>
        </div>
        <editor-content :editor="editor" />
    </div>
</template>

<style>
.tiptap-button-group {
    display: flex;
    gap: 4px;
    padding: 4px 8px;
    border-bottom: 1px solid var(--border);
    background-color: #fafafa;
}

.tiptap-button-group button {
    background-color: transparent;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
}

.tiptap-button-group button.is-active {
    background-color: #eee;
}

.tiptap-button-group button.is-active svg {
    color: darkred;
}

.tiptap-button-group button:hover {
    background-color: #ddd;
}

.tiptap-button-group button svg {
    color: #333;
}

.tiptap-button-group button:disabled svg {
    color: #999;
}

.tiptap-button-group .tiptap-separator {
    width: 1px;
    background-color: #e0e0e0;
    margin: 0 4px;
}

.tiptap {
    min-height: 150px;
    padding: 10px 10px !important;
    outline: none;
}

.tiptap:focus-visible {
    outline: none;
}

.tiptap h1 {
    font-size: 2em;
    margin-bottom: 0.5em;
}
.tiptap h2 {
    font-size: 1.5em;
    margin-bottom: 0.5em;
}
.tiptap h3 {
    font-size: 1.17em;
    margin-bottom: 0.5em;
}

.tiptap blockquote {
    border-left: 4px solid #ccc;
    margin-left: 0;
    margin-right: 0;
    padding-left: 1em;
    color: #666;
    font-style: italic;
}

.tiptap ul {
    list-style-type: disc;
    margin-left: 1.5em;
    margin-bottom: 1em;
}

.tiptap ol {
    list-style-type: decimal;
    margin-left: 1.5em;
    margin-bottom: 1em;
}
</style>
