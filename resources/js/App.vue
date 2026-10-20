<template>
  <div id="app">
    <div v-if="hasError" class="error-boundary">
      <div class="error-boundary__content">
        <h1>Terjadi Kesalahan</h1>
        <p>Maaf, terjadi kesalahan yang tidak terduga pada aplikasi.</p>
        <p v-if="errorMessage" class="error-boundary__message">
          {{ errorMessage }}
        </p>
        <button class="error-boundary__retry" @click="handleRetry">
          Coba Lagi
        </button>
      </div>
    </div>
    <router-view v-else />
  </div>
</template>

<script setup>
import { ref, onErrorCaptured } from "vue";

const hasError = ref(false);
const errorMessage = ref("");

onErrorCaptured((error) => {
  hasError.value = true;
  errorMessage.value = error?.message || "Kesalahan tidak diketahui";
  return false;
});

function handleRetry() {
  hasError.value = false;
  errorMessage.value = "";
}
</script>

<style scoped>
.error-boundary {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 1rem;
  background-color: #fef2f2;
}

.error-boundary__content {
  text-align: center;
  max-width: 480px;
}

.error-boundary__content h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #991b1b;
  margin-bottom: 0.5rem;
}

.error-boundary__content p {
  color: #7f1d1d;
  margin-bottom: 0.5rem;
}

.error-boundary__message {
  font-size: 0.875rem;
  color: #b91c1c;
  background-color: #fee2e2;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.error-boundary__retry {
  padding: 0.5rem 1.5rem;
  background-color: #dc2626;
  color: white;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
}

.error-boundary__retry:hover {
  background-color: #b91c1c;
}
</style>
