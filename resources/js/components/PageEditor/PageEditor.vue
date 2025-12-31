<script setup lang="ts">
import { FileManagerDialog } from '@/components/FileManager';
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

const handleImageSelect = (files: MediaFile[]) => {
  if (files.length > 0 && files[0].url && editor.value) {
    editor.value.chain().focus().setImage({ src: files[0].url, alt: files[0].name }).run();
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
    <PageEditorToolbar v-if="editor" :editor="editor" @open-file-manager="openFileManager" />
    <EditorContent :editor="editor" />

    <FileManagerDialog
      v-model:open="showFileManager"
      title="Insert Image"
      description="Select an image from your media library to insert into the content"
      selection-mode="single"
      :accept-types="['image']"
      @confirm="handleImageSelect"
    />
  </div>
</template>
