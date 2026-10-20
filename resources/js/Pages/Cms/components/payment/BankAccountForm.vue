<template>
  <div class="rounded-lg border border-gray-200 bg-white p-5">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
      {{ isEditing ? "Edit Rekening Bank" : "Tambah Rekening Bank" }}
    </h3>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Bank Name -->
      <div>
        <label
          for="bankName"
          class="block text-sm font-medium text-gray-700 mb-1"
        >
          Nama Bank <span class="text-red-500">*</span>
        </label>
        <input
          id="bankName"
          v-model="form.bankName"
          type="text"
          class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          :class="errors.bankName ? 'border-red-300' : 'border-gray-300'"
          placeholder="Contoh: BCA, Mandiri, BNI"
        />
        <p
          v-if="errors.bankName"
          class="mt-1 text-xs text-red-600"
          role="alert"
        >
          {{ errors.bankName }}
        </p>
      </div>

      <!-- Account Number -->
      <div>
        <label
          for="accountNumber"
          class="block text-sm font-medium text-gray-700 mb-1"
        >
          Nomor Rekening <span class="text-red-500">*</span>
        </label>
        <input
          id="accountNumber"
          v-model="form.accountNumber"
          type="text"
          class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          :class="errors.accountNumber ? 'border-red-300' : 'border-gray-300'"
          placeholder="Contoh: 1234567890"
        />
        <p
          v-if="errors.accountNumber"
          class="mt-1 text-xs text-red-600"
          role="alert"
        >
          {{ errors.accountNumber }}
        </p>
      </div>

      <!-- Account Holder -->
      <div>
        <label
          for="accountHolder"
          class="block text-sm font-medium text-gray-700 mb-1"
        >
          Nama Pemilik Rekening <span class="text-red-500">*</span>
        </label>
        <input
          id="accountHolder"
          v-model="form.accountHolder"
          type="text"
          class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          :class="errors.accountHolder ? 'border-red-300' : 'border-gray-300'"
          placeholder="Contoh: Budi Santoso"
        />
        <p
          v-if="errors.accountHolder"
          class="mt-1 text-xs text-red-600"
          role="alert"
        >
          {{ errors.accountHolder }}
        </p>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3 pt-2">
        <button
          type="submit"
          class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="submitting"
        >
          <span v-if="submitting">Menyimpan...</span>
          <span v-else>{{ isEditing ? "Perbarui" : "Tambah" }}</span>
        </button>
        <button
          type="button"
          class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
          @click="$emit('cancel')"
        >
          Batal
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from "vue";

const props = defineProps({
  account: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["submit", "cancel"]);

const isEditing = ref(!!props.account);
const submitting = ref(false);

const form = reactive({
  bankName: "",
  accountNumber: "",
  accountHolder: "",
});

const errors = reactive({
  bankName: "",
  accountNumber: "",
  accountHolder: "",
});

// Populate form when editing
watch(
  () => props.account,
  (val) => {
    if (val) {
      form.bankName = val.bankName || "";
      form.accountNumber = val.accountNumber || "";
      form.accountHolder = val.accountHolder || "";
      isEditing.value = true;
    } else {
      form.bankName = "";
      form.accountNumber = "";
      form.accountHolder = "";
      isEditing.value = false;
    }
  },
  { immediate: true },
);

function validate() {
  let valid = true;
  errors.bankName = "";
  errors.accountNumber = "";
  errors.accountHolder = "";

  if (!form.bankName.trim()) {
    errors.bankName = "Nama bank wajib diisi";
    valid = false;
  }
  if (!form.accountNumber.trim()) {
    errors.accountNumber = "Nomor rekening wajib diisi";
    valid = false;
  }
  if (!form.accountHolder.trim()) {
    errors.accountHolder = "Nama pemilik rekening wajib diisi";
    valid = false;
  }

  return valid;
}

async function handleSubmit() {
  if (!validate()) return;

  submitting.value = true;
  try {
    emit("submit", {
      bankName: form.bankName.trim(),
      accountNumber: form.accountNumber.trim(),
      accountHolder: form.accountHolder.trim(),
    });
  } finally {
    submitting.value = false;
  }
}

defineExpose({ validate });
</script>
