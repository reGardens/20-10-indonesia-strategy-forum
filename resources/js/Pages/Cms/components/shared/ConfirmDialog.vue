<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="visible"
        class="fixed inset-0 z-50 flex items-center justify-center"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        :aria-describedby="messageId"
        @keydown.escape="$emit('cancel')"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/50 transition-opacity"
          @click="$emit('cancel')"
        />

        <!-- Dialog panel -->
        <div
          ref="dialogPanel"
          class="relative z-10 w-full max-w-md rounded-xl bg-white p-6 shadow-xl mx-4"
          tabindex="-1"
        >
          <h2 :id="titleId" class="text-lg font-semibold text-gray-900">
            {{ title }}
          </h2>
          <p :id="messageId" class="mt-2 text-sm text-gray-600">
            {{ message }}
          </p>

          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
              @click="$emit('cancel')"
            >
              {{ cancelText }}
            </button>
            <button
              type="button"
              class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
              @click="$emit('confirm')"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from "vue";

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    required: true,
  },
  confirmText: {
    type: String,
    default: "Hapus",
  },
  cancelText: {
    type: String,
    default: "Batal",
  },
});

defineEmits(["confirm", "cancel"]);

const dialogPanel = ref(null);
const titleId = `confirm-dialog-title-${Math.random().toString(36).slice(2, 9)}`;
const messageId = `confirm-dialog-message-${Math.random().toString(36).slice(2, 9)}`;

watch(
  () => props.visible,
  async (newVal) => {
    if (newVal) {
      await nextTick();
      dialogPanel.value?.focus();
    }
  },
);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
