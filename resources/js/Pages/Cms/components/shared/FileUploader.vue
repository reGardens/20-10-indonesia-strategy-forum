<template>
  <div
    class="relative rounded-lg border-2 border-dashed transition-colors"
    :class="
      isDragOver
        ? 'border-emerald-500 bg-emerald-50'
        : 'border-gray-300 bg-gray-50 hover:border-gray-400'
    "
    @dragenter.prevent="onDragEnter"
    @dragover.prevent="onDragOver"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <label
      :for="inputId"
      class="flex flex-col items-center justify-center gap-2 cursor-pointer px-6 py-8"
    >
      <span class="text-3xl" aria-hidden="true">📁</span>
      <span class="text-sm font-medium text-gray-700">
        Seret file ke sini atau
        <span class="text-emerald-600 underline">pilih file</span>
      </span>
      <span v-if="accept" class="text-xs text-gray-400">
        Format: {{ accept }}
      </span>
      <span v-if="maxSizeMB" class="text-xs text-gray-400">
        Maks. {{ maxSizeMB }}MB per file
      </span>
    </label>

    <input
      :id="inputId"
      ref="fileInput"
      type="file"
      :accept="accept"
      :multiple="multiple"
      class="sr-only"
      @change="onFileSelect"
    />

    <p v-if="errorText" class="px-4 pb-3 text-xs text-red-600" role="alert">
      {{ errorText }}
    </p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  accept: {
    type: String,
    default: "",
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  maxSizeMB: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(["files-selected"]);

const isDragOver = ref(false);
const errorText = ref("");
const fileInput = ref(null);
const inputId = `file-uploader-${Math.random().toString(36).slice(2, 9)}`;

let dragCounter = 0;

function onDragEnter() {
  dragCounter++;
  isDragOver.value = true;
}

function onDragOver() {
  isDragOver.value = true;
}

function onDragLeave() {
  dragCounter--;
  if (dragCounter <= 0) {
    dragCounter = 0;
    isDragOver.value = false;
  }
}

function onDrop(event) {
  dragCounter = 0;
  isDragOver.value = false;
  const files = Array.from(event.dataTransfer?.files || []);
  processFiles(files);
}

function onFileSelect(event) {
  const files = Array.from(event.target.files || []);
  processFiles(files);
  // Reset input so the same file can be selected again
  if (fileInput.value) {
    fileInput.value.value = "";
  }
}

function processFiles(files) {
  errorText.value = "";

  if (!files.length) return;

  const filesToEmit = props.multiple ? files : [files[0]];

  if (props.maxSizeMB > 0) {
    const maxBytes = props.maxSizeMB * 1024 * 1024;
    const oversized = filesToEmit.filter((f) => f.size > maxBytes);
    if (oversized.length > 0) {
      const names = oversized.map((f) => f.name).join(", ");
      errorText.value = `File terlalu besar (maks. ${props.maxSizeMB}MB): ${names}`;
      return;
    }
  }

  emit("files-selected", filesToEmit);
}
</script>
