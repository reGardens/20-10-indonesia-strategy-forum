<template>
  <div
    class="password-gate flex min-h-screen flex-col items-center justify-center px-4"
  >
    <div class="w-full max-w-sm text-center" data-aos="fade-up">
      <div class="mb-6 text-5xl">🔒</div>
      <h2 class="mb-2 text-xl font-semibold text-gray-800">
        Undangan Terkunci
      </h2>
      <p class="mb-6 text-sm text-gray-500">
        Masukkan password untuk melihat undangan
      </p>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <input
            v-model="inputPassword"
            type="password"
            placeholder="Masukkan password"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-center text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            :class="{
              'border-red-400 focus:border-red-500 focus:ring-red-200':
                errorMessage,
            }"
            autocomplete="off"
          />
          <p v-if="errorMessage" class="mt-2 text-sm text-red-500">
            {{ errorMessage }}
          </p>
        </div>

        <button
          type="submit"
          class="w-full rounded-lg bg-amber-600 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-300"
        >
          Buka Undangan
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  password: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(["authenticated"]);

const inputPassword = ref("");
const errorMessage = ref("");

function handleSubmit() {
  errorMessage.value = "";

  if (!inputPassword.value) {
    errorMessage.value = "Password wajib diisi";
    return;
  }

  if (inputPassword.value === props.password) {
    emit("authenticated");
  } else {
    errorMessage.value = "Password salah";
    inputPassword.value = "";
  }
}
</script>
