<template>
  <div class="guest-form rounded-lg border border-gray-200 bg-white p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-900">
        {{ isEditing ? "Edit Tamu" : "Tambah Tamu" }}
      </h2>
      <button
        type="button"
        class="text-gray-400 hover:text-gray-600 transition-colors"
        aria-label="Tutup formulir"
        @click="$emit('cancel')"
      >
        <svg
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>

    <form @submit.prevent="handleSubmit" novalidate>
      <!-- Name (required) -->
      <div class="mb-4">
        <label
          :for="`${formId}-name`"
          class="block text-sm font-medium text-gray-700 mb-1"
        >
          Nama Tamu <span class="text-red-500">*</span>
        </label>
        <input
          :id="`${formId}-name`"
          v-model="form.name"
          type="text"
          class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          :class="errors.name ? 'border-red-300' : 'border-gray-300'"
          placeholder="Contoh: Bpk. Ahmad Fauzi"
          @blur="validateName"
        />
        <p v-if="errors.name" class="mt-1 text-xs text-red-600" role="alert">
          {{ errors.name }}
        </p>
      </div>

      <!-- Phone -->
      <div class="mb-4">
        <label
          :for="`${formId}-phone`"
          class="block text-sm font-medium text-gray-700 mb-1"
        >
          No. Telepon
        </label>
        <input
          :id="`${formId}-phone`"
          v-model="form.phone"
          type="tel"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          placeholder="Contoh: 628123456789"
        />
      </div>

      <!-- Max Pax -->
      <div class="mb-4">
        <label
          :for="`${formId}-maxPax`"
          class="block text-sm font-medium text-gray-700 mb-1"
        >
          Jumlah Tamu Maksimal
        </label>
        <input
          :id="`${formId}-maxPax`"
          v-model.number="form.maxPax"
          type="number"
          min="1"
          max="10"
          class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          :class="errors.maxPax ? 'border-red-300' : 'border-gray-300'"
          @blur="validateMaxPax"
        />
        <p v-if="errors.maxPax" class="mt-1 text-xs text-red-600" role="alert">
          {{ errors.maxPax }}
        </p>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3">
        <button
          type="button"
          class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
          @click="$emit('cancel')"
        >
          Batal
        </button>
        <button
          type="submit"
          class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="saving"
        >
          <span v-if="saving">Menyimpan...</span>
          <span v-else>{{ isEditing ? "Perbarui" : "Simpan" }}</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, watch } from "vue";

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      name: "",
      phone: "",
      maxPax: 1,
    }),
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["submit", "cancel"]);

const formId = `guest-form-${Math.random().toString(36).slice(2, 9)}`;

const form = reactive({
  name: props.modelValue.name || "",
  phone: props.modelValue.phone || "",
  maxPax: props.modelValue.maxPax ?? 1,
});

const errors = reactive({
  name: "",
  maxPax: "",
});

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      form.name = newVal.name || "";
      form.phone = newVal.phone || "";
      form.maxPax = newVal.maxPax ?? 1;
    }
  },
  { deep: true },
);

function validateName() {
  if (!form.name.trim()) {
    errors.name = "Nama tamu wajib diisi";
  } else {
    errors.name = "";
  }
}

function validateMaxPax() {
  const val = Number(form.maxPax);
  if (isNaN(val) || val < 1) {
    errors.maxPax = "Jumlah tamu minimal 1";
  } else if (val > 10) {
    errors.maxPax = "Jumlah tamu maksimal 10";
  } else {
    errors.maxPax = "";
  }
}

function validateAll() {
  validateName();
  validateMaxPax();
  return !errors.name && !errors.maxPax;
}

function handleSubmit() {
  if (!validateAll()) return;

  const data = {
    name: form.name.trim(),
    phone: form.phone.trim(),
    maxPax: Number(form.maxPax) || 1,
  };

  emit("submit", data);
}

defineExpose({ validate: validateAll });
</script>
