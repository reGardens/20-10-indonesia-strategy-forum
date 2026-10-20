<template>
  <div class="couple-view space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Data Mempelai</h1>

    <!-- Loading state -->
    <LoadingIndicator v-if="loading" text="Memuat data mempelai..." />

    <!-- Error state -->
    <ErrorMessage
      v-else-if="error"
      :message="error"
      :retryable="true"
      @retry="loadData"
    />

    <template v-else>
      <!-- Groom & Bride forms side by side -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <CoupleForm
          ref="groomFormRef"
          title="Mempelai Pria"
          :model-value="groomData"
          @update:model-value="groomData = $event"
        />
        <CoupleForm
          ref="brideFormRef"
          title="Mempelai Wanita"
          :model-value="brideData"
          @update:model-value="brideData = $event"
        />
      </div>

      <!-- Music URL -->
      <div class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Musik Latar</h2>
        <div>
          <label
            for="musicUrl"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            URL Musik
          </label>
          <input
            id="musicUrl"
            v-model="musicUrl"
            type="text"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            placeholder="Contoh: media/background-music.mp3"
          />
        </div>
      </div>

      <!-- Save button -->
      <div class="flex justify-end">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="saving"
          @click="handleSave"
        >
          <span v-if="saving">Menyimpan...</span>
          <span v-else>Simpan</span>
        </button>
      </div>

      <!-- Validation error -->
      <p v-if="validationError" class="text-sm text-red-600" role="alert">
        {{ validationError }}
      </p>

      <!-- Success message -->
      <p v-if="successMessage" class="text-sm text-green-600" role="status">
        {{ successMessage }}
      </p>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useStore } from "vuex";
import CoupleForm from "@/Pages/Cms/components/couple/CoupleForm.vue";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";

const store = useStore();

const groomFormRef = ref(null);
const brideFormRef = ref(null);

const groomData = ref({
  fullName: "",
  nickname: "",
  photo: "",
  fatherName: "",
  motherName: "",
  instagramUrl: "",
  childOrder: "",
});

const brideData = ref({
  fullName: "",
  nickname: "",
  photo: "",
  fatherName: "",
  motherName: "",
  instagramUrl: "",
  childOrder: "",
});

const musicUrl = ref("");
const saving = ref(false);
const validationError = ref("");
const successMessage = ref("");

const loading = computed(() => store.getters["couple/isLoading"]);
const error = computed(() => store.getters["couple/error"]);

/**
 * Capitalize first letter of a string.
 * e.g., "reza" → "Reza"
 */
function capitalize(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Extract groom and bride names from the wedding slug.
 * e.g., "reza-rita" → { groom: "Reza", bride: "Rita" }
 * e.g., "budi-dan-ani" → { groom: "Budi", bride: "Ani" }
 */
function namesFromSlug() {
  const slug = store.getters["wedding/activeSlug"];
  if (!slug) return { groom: "", bride: "" };

  const parts = slug.split("-");
  if (parts.length >= 2) {
    // Take first part as groom, last part as bride
    return {
      groom: capitalize(parts[0]),
      bride: capitalize(parts[parts.length - 1]),
    };
  }
  return { groom: capitalize(parts[0] || ""), bride: "" };
}

function populateFromStore() {
  const data = store.getters["couple/couple"];
  if (data) {
    if (data.groom) {
      groomData.value = { ...groomData.value, ...data.groom };
    }
    if (data.bride) {
      brideData.value = { ...brideData.value, ...data.bride };
    }
    musicUrl.value = data.musicUrl || "";
  }

  // Auto-fill from slug if names are still empty (first time)
  const slugNames = namesFromSlug();
  if (!groomData.value.fullName && slugNames.groom) {
    groomData.value.fullName = slugNames.groom;
    groomData.value.nickname = slugNames.groom;
  }
  if (!brideData.value.fullName && slugNames.bride) {
    brideData.value.fullName = slugNames.bride;
    brideData.value.nickname = slugNames.bride;
  }
}

async function loadData() {
  await store.dispatch("couple/fetchCouple");
  populateFromStore();
}

async function handleSave() {
  validationError.value = "";
  successMessage.value = "";

  // Validate both forms
  const groomValid = groomFormRef.value?.validate() ?? true;
  const brideValid = brideFormRef.value?.validate() ?? true;

  if (!groomValid || !brideValid) {
    validationError.value = "Nama lengkap wajib diisi untuk kedua mempelai.";
    return;
  }

  saving.value = true;

  try {
    const existingData = store.getters["couple/couple"] || {};

    const payload = {
      ...existingData,
      groom: { ...groomData.value },
      bride: { ...brideData.value },
      musicUrl: musicUrl.value,
      updatedAt: new Date().toISOString(),
    };

    if (!payload.createdAt) {
      payload.createdAt = new Date().toISOString();
    }

    await store.dispatch("couple/saveCouple", payload);
    successMessage.value = "Data mempelai berhasil disimpan.";

    // Clear success message after 3 seconds
    setTimeout(() => {
      successMessage.value = "";
    }, 3000);
  } catch (err) {
    validationError.value = err.message || "Gagal menyimpan data mempelai.";
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  loadData();
});
</script>
