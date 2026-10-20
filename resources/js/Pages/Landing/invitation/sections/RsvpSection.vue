<template>
  <section class="rsvp-section px-4 py-16 md:py-24" data-aos="fade-up">
    <div class="mx-auto max-w-lg text-center">
      <h2
        class="mb-4 text-2xl font-bold md:text-3xl"
        :style="{ color: primaryColor }"
      >
        Konfirmasi Kehadiran
      </h2>
      <p class="mb-8 text-gray-600">Mohon konfirmasi kehadiran Anda</p>

      <!-- Success State -->
      <div
        v-if="submitted"
        class="rounded-xl bg-green-50 p-8"
        data-aos="zoom-in"
      >
        <div class="mb-4 text-4xl">✅</div>
        <h3 class="text-lg font-semibold text-green-800">Terima Kasih!</h3>
        <p class="mt-2 text-sm text-green-600">
          Konfirmasi kehadiran Anda telah kami terima.
        </p>
      </div>

      <!-- RSVP Form -->
      <form v-else class="space-y-4 text-left" @submit.prevent="handleSubmit">
        <!-- Name -->
        <div>
          <label
            for="rsvp-name"
            class="mb-1 block text-sm font-medium text-gray-700"
          >
            Nama <span class="text-red-500">*</span>
          </label>
          <input
            id="rsvp-name"
            v-model="form.name"
            type="text"
            placeholder="Masukkan nama Anda"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            :class="{ 'border-red-400': errors.name }"
          />
          <p v-if="errors.name" class="mt-1 text-xs text-red-500">
            {{ errors.name }}
          </p>
        </div>

        <!-- Attendance Status -->
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">
            Status Kehadiran <span class="text-red-500">*</span>
          </label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="option in statusOptions"
              :key="option.value"
              type="button"
              class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
              :class="
                form.status === option.value
                  ? 'border-transparent text-white'
                  : 'border-gray-300 text-gray-600 hover:bg-gray-50'
              "
              :style="
                form.status === option.value
                  ? { backgroundColor: primaryColor }
                  : {}
              "
              @click="form.status = option.value"
            >
              {{ option.icon }} {{ option.label }}
            </button>
          </div>
          <p v-if="errors.status" class="mt-1 text-xs text-red-500">
            {{ errors.status }}
          </p>
        </div>

        <!-- Number of Guests -->
        <div v-if="form.status === 'Hadir'">
          <label
            for="rsvp-pax"
            class="mb-1 block text-sm font-medium text-gray-700"
          >
            Jumlah Tamu
          </label>
          <select
            id="rsvp-pax"
            v-model.number="form.numberOfGuests"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
          >
            <option v-for="n in 10" :key="n" :value="n">{{ n }} orang</option>
          </select>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          class="w-full rounded-lg px-6 py-3 text-sm font-medium text-white transition-colors hover:opacity-90 disabled:opacity-50"
          :style="{ backgroundColor: primaryColor }"
          :disabled="submitting"
        >
          {{ submitting ? "Mengirim..." : "Kirim Konfirmasi" }}
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed } from "vue";
import { useRsvpForm } from "../composables/useRsvpForm.js";

const props = defineProps({
  themeColors: {
    type: Object,
    default: () => ({}),
  },
});

const primaryColor = computed(() => props.themeColors?.primary || "#8B4513");

const { form, errors, submitting, submitted, submitRsvp } = useRsvpForm();

const statusOptions = [
  { value: "Hadir", label: "Hadir", icon: "✅" },
  { value: "Tidak Hadir", label: "Tidak Hadir", icon: "❌" },
  { value: "Mungkin", label: "Mungkin", icon: "🤔" },
];

function handleSubmit() {
  submitRsvp();
}
</script>
