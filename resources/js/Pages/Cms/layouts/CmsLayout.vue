<template>
  <div class="cms-layout flex h-screen overflow-hidden bg-gray-100">
    <!-- Sidebar (hidden on mobile, shown on md+) -->
    <aside class="hidden md:flex flex-shrink-0">
      <SidebarNav />
    </aside>

    <!-- Mobile sidebar overlay -->
    <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 flex md:hidden">
      <div
        class="fixed inset-0 bg-black/50"
        @click="mobileMenuOpen = false"
      ></div>
      <div class="relative z-50">
        <SidebarNav />
      </div>
    </div>

    <!-- Main content area -->
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
      <!-- Mobile top bar -->
      <header
        class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 md:hidden"
      >
        <button
          type="button"
          class="p-1.5 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500"
          aria-label="Open menu"
          @click="mobileMenuOpen = true"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>
        <div class="flex-1 min-w-0">
          <span
            v-if="activeWedding"
            class="text-lg font-semibold text-gray-800 truncate block"
          >
            {{ activeWedding.label }}
          </span>
          <span v-else class="text-lg font-semibold text-gray-800"
            >Wedding CMS</span
          >
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <div v-if="viewError" class="space-y-4">
          <ErrorMessage
            :message="viewError"
            :retryable="true"
            @retry="handleRetry"
          />
        </div>
        <router-view v-else />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onErrorCaptured } from "vue";
import { useRouter } from "vue-router";
import { useStore } from "vuex";
import SidebarNav from "../components/shared/SidebarNav.vue";
import ErrorMessage from "../components/shared/ErrorMessage.vue";

const store = useStore();
const mobileMenuOpen = ref(false);
const viewError = ref("");

const activeWedding = computed(() => store.getters["wedding/activeWedding"]);

// Catch unexpected errors from child CMS views
onErrorCaptured((error) => {
  viewError.value =
    error?.message ||
    "Terjadi kesalahan yang tidak terduga. Silakan coba lagi.";
  return false;
});

function handleRetry() {
  viewError.value = "";
}

// Close mobile menu on route change and clear errors
const router = useRouter();
router.afterEach(() => {
  mobileMenuOpen.value = false;
  viewError.value = "";
});
</script>
