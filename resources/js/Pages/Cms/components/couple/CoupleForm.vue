<template>
  <div class="couple-form rounded-lg border border-gray-200 bg-white p-5">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ title }}</h2>

    <!-- Photo -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
      <PhotoUploader
        :model-value="form.photo"
        :alt="`Foto ${title}`"
        @update:model-value="form.photo = $event"
        @optimized="onPhotoOptimized"
      />
    </div>

    <!-- Full Name (required) -->
    <div class="mb-4">
      <label
        :for="`${formId}-fullName`"
        class="block text-sm font-medium text-gray-700 mb-1"
      >
        Nama Lengkap <span class="text-red-500">*</span>
      </label>
      <input
        :id="`${formId}-fullName`"
        v-model="form.fullName"
        type="text"
        class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        :class="errors.fullName ? 'border-red-300' : 'border-gray-300'"
        placeholder="Contoh: Budi Santoso"
        @blur="validateFullName"
      />
      <p v-if="errors.fullName" class="mt-1 text-xs text-red-600" role="alert">
        {{ errors.fullName }}
      </p>
    </div>

    <!-- Nickname -->
    <div class="mb-4">
      <label
        :for="`${formId}-nickname`"
        class="block text-sm font-medium text-gray-700 mb-1"
      >
        Nama Panggilan
      </label>
      <input
        :id="`${formId}-nickname`"
        v-model="form.nickname"
        type="text"
        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        placeholder="Contoh: Budi"
      />
    </div>

    <!-- Father Name -->
    <div class="mb-4">
      <label
        :for="`${formId}-fatherName`"
        class="block text-sm font-medium text-gray-700 mb-1"
      >
        Nama Ayah
      </label>
      <input
        :id="`${formId}-fatherName`"
        v-model="form.fatherName"
        type="text"
        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        placeholder="Contoh: H. Santoso"
      />
    </div>

    <!-- Mother Name -->
    <div class="mb-4">
      <label
        :for="`${formId}-motherName`"
        class="block text-sm font-medium text-gray-700 mb-1"
      >
        Nama Ibu
      </label>
      <input
        :id="`${formId}-motherName`"
        v-model="form.motherName"
        type="text"
        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        placeholder="Contoh: Hj. Siti Aminah"
      />
    </div>

    <!-- Instagram URL -->
    <div class="mb-4">
      <label
        :for="`${formId}-instagramUrl`"
        class="block text-sm font-medium text-gray-700 mb-1"
      >
        Instagram
      </label>
      <input
        :id="`${formId}-instagramUrl`"
        v-model="form.instagramUrl"
        type="url"
        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        placeholder="https://instagram.com/username"
      />
    </div>

    <!-- Child Order -->
    <div class="mb-4">
      <label
        :for="`${formId}-childOrder`"
        class="block text-sm font-medium text-gray-700 mb-1"
      >
        Anak ke-
      </label>
      <input
        :id="`${formId}-childOrder`"
        v-model="form.childOrder"
        type="text"
        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        placeholder="Contoh: Putra pertama"
      />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from "vue";
import PhotoUploader from "./PhotoUploader.vue";

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  modelValue: {
    type: Object,
    default: () => ({
      fullName: "",
      nickname: "",
      photo: "",
      fatherName: "",
      motherName: "",
      instagramUrl: "",
      childOrder: "",
    }),
  },
});

const emit = defineEmits(["update:modelValue"]);

const formId = `couple-form-${Math.random().toString(36).slice(2, 9)}`;

const form = reactive({
  fullName: props.modelValue.fullName || "",
  nickname: props.modelValue.nickname || "",
  photo: props.modelValue.photo || "",
  fatherName: props.modelValue.fatherName || "",
  motherName: props.modelValue.motherName || "",
  instagramUrl: props.modelValue.instagramUrl || "",
  childOrder: props.modelValue.childOrder || "",
});

const errors = reactive({
  fullName: "",
});

// Sync incoming prop changes to form
watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      form.fullName = newVal.fullName || "";
      form.nickname = newVal.nickname || "";
      form.photo = newVal.photo || "";
      form.fatherName = newVal.fatherName || "";
      form.motherName = newVal.motherName || "";
      form.instagramUrl = newVal.instagramUrl || "";
      form.childOrder = newVal.childOrder || "";
    }
  },
  { deep: true },
);

// Emit changes to parent
watch(
  form,
  (newForm) => {
    emit("update:modelValue", { ...newForm });
  },
  { deep: true },
);

function validateFullName() {
  if (!form.fullName.trim()) {
    errors.fullName = "Nama lengkap wajib diisi";
  } else {
    errors.fullName = "";
  }
}

function onPhotoOptimized(result) {
  // Parent can listen for additional photo metadata if needed
}

/**
 * Validate the form and return whether it's valid.
 * Called by parent component before saving.
 */
function validate() {
  validateFullName();
  return !errors.fullName;
}

defineExpose({ validate });
</script>
