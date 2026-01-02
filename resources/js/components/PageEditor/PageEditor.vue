<script setup lang="ts">
import { FileManagerDialog } from '@/components/FileManager';
import MarkdownEditor from '@/components/PageEditor/MarkdownEditor.vue';
import PageEditorToolbar from '@/components/PageEditor/PageEditorToolbar.vue';
import { useCspNonce } from '@/composables/useCspNonce';
import { htmlToMarkdown } from '@/composables/useHtmlToMarkdown';
import { markdownToHtml } from '@/composables/useMarkdownToHtml';
import type { MediaFile } from '@/types/media';
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import Image from '@tiptap/extension-image';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { common, createLowlight } from 'lowlight';
import { ref, watch } from 'vue';

interface Props {
  modelValue: string;
  placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Start writing your content...',
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const lowlight = createLowlight(common);

const showFileManager = ref(false);
const isMarkdownMode = ref(false);

const cspNonce = useCspNonce();

const editor = useEditor({
  content: markdownToHtml(props.modelValue),
  extensions: [
    StarterKit.configure({
      codeBlock: false,
      link: {
        openOnClick: false,
        HTMLAttributes: {
          class: 'text-primary underline',
        },
      },
    }),
    CodeBlockLowlight.configure({
      lowlight,
      HTMLAttributes: {
        class: 'rounded-md bg-muted p-4 font-mono text-sm',
      },
    }),
    Image.configure({
      HTMLAttributes: {
        class: 'rounded-md max-w-full',
      },
    }),
  ],
  editorProps: {
    attributes: {
      class: 'prose prose-sm dark:prose-invert max-w-none min-h-[300px] focus:outline-none p-4',
    },
  },
  injectCSS: true,
  injectNonce: cspNonce ?? undefined,
  onUpdate: ({ editor }) => {
    const html = editor.getHTML();
    const markdown = htmlToMarkdown(html);
    emit('update:modelValue', markdown);
  },
});

const openFileManager = () => {
  showFileManager.value = true;
};

const toggleMarkdownMode = () => {
  if (isMarkdownMode.value && editor.value) {
    editor.value.commands.setContent(markdownToHtml(props.modelValue), { emitUpdate: false });
  }
  isMarkdownMode.value = !isMarkdownMode.value;
};

const handleMarkdownUpdate = (value: string) => {
  emit('update:modelValue', value);
};

const handleFileSelect = (files: MediaFile[]) => {
  if (files.length > 0 && files[0].url && editor.value) {
    const file = files[0];

    switch (file.file_type) {
      case 'image':
        editor.value.chain().focus().setImage({ src: file.url, alt: file.name }).run();
        break;
      case 'video':
        editor.value
          .chain()
          .focus()
          .insertContent(
            `<video controls class="rounded-md max-w-full"><source src="${file.url}" type="${file.mime_type}">Your browser does not support the video tag.</video>`,
          )
          .run();
        break;
      case 'audio':
        editor.value
          .chain()
          .focus()
          .insertContent(
            `<audio controls class="w-full"><source src="${file.url}" type="${file.mime_type}">Your browser does not support the audio tag.</audio>`,
          )
          .run();
        break;
      default:
        editor.value
          .chain()
          .focus()
          .insertContent(`<a href="${file.url}" target="_blank">${file.name}</a>`)
          .run();
    }
  }
  showFileManager.value = false;
};

watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value) {
      return;
    }
    const currentMarkdown = htmlToMarkdown(editor.value.getHTML());
    if (currentMarkdown !== value) {
      editor.value.commands.setContent(markdownToHtml(value), { emitUpdate: false });
    }
  },
);
</script>

<template>
  <div class="rounded-lg border bg-background">
    <PageEditorToolbar
      v-if="editor"
      :editor="editor"
      :is-markdown-mode="isMarkdownMode"
      @open-file-manager="openFileManager"
      @toggle-markdown-mode="toggleMarkdownMode"
    />
    <MarkdownEditor
      v-if="isMarkdownMode"
      :model-value="modelValue"
      :placeholder="placeholder"
      @update:model-value="handleMarkdownUpdate"
    />
    <EditorContent v-else :editor="editor" />

    <FileManagerDialog
      v-model:open="showFileManager"
      title="Insert Media"
      description="Select a file from your media library to insert into the content"
      selection-mode="single"
      :accept-types="['image', 'document', 'video', 'audio']"
      @confirm="handleFileSelect"
    />
  </div>
</template>
