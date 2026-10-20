<template>
  <div class="media-view space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Perpustakaan Media</h1>

    <!-- Loading state -->
    <LoadingIndicator v-if="loading" text="Memuat media..." />

    <!-- Error state -->
    <ErrorMessage
      v-else-if="error"
      :message="error"
      :retryable="true"
      @retry="loadData"
    />

    <template v-else>
      <!-- Upload area -->
      <MediaUploader />

      <!-- Gallery layout picker -->
      <GalleryLayoutPicker />

      <!-- Media grid -->
      <MediaGrid />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { useStore } from "vuex";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import MediaUploader from "@/Pages/Cms/components/media/MediaUploader.vue";
import MediaGrid from "@/Pages/Cms/components/media/MediaGrid.vue";
import GalleryLayoutPicker from "@/Pages/Cms/components/media/GalleryLayoutPicker.vue";

const store = useStore();

const loading = computed(() => store.getters["media/isLoading"]);
const error = computed(() => store.getters["media/error"]);

async function loadData() {
  await store.dispatch("media/fetchMedia");
}

onMounted(() => {
  loadData();
});
</script>
