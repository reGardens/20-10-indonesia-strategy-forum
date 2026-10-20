<template>
  <section class="gift-section px-4 py-16 md:py-24" data-aos="fade-up">
    <div class="mx-auto max-w-lg text-center">
      <h2
        class="mb-4 text-2xl font-bold md:text-3xl"
        :style="{ color: primaryColor }"
      >
        Amplop Digital
      </h2>
      <p class="mb-8 text-sm text-gray-600">
        Doa dan restu Anda merupakan hadiah terindah bagi kami
      </p>

      <!-- Bank Accounts -->
      <div v-if="bankAccounts && bankAccounts.length" class="mb-6 space-y-4">
        <div
          v-for="account in bankAccounts"
          :key="account.id"
          class="rounded-xl bg-white/90 p-5 shadow-md backdrop-blur-sm"
          data-aos="fade-up"
        >
          <p class="text-sm font-semibold text-gray-800">
            {{ account.bankName || "Bank" }}
          </p>
          <div class="mt-2 flex items-center justify-center gap-2">
            <p class="text-lg font-bold tracking-wider text-gray-700">
              {{ account.accountNumber || "-" }}
            </p>
            <button
              class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
              :title="
                copiedAccountId === account.id
                  ? 'Tersalin!'
                  : 'Salin nomor rekening'
              "
              @click="copyAccountNumber(account)"
            >
              <svg
                v-if="copiedAccountId !== account.id"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                />
              </svg>
              <svg
                v-else
                class="h-4 w-4 text-green-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </button>
          </div>
          <p class="mt-1 text-sm text-gray-500">
            a.n. {{ account.accountHolder || "-" }}
          </p>
        </div>
      </div>

      <!-- QRIS Image -->
      <div v-if="qrisImageUrl" class="mb-6" data-aos="fade-up">
        <p class="mb-3 text-sm font-medium text-gray-700">Scan QRIS</p>
        <div
          class="mx-auto inline-block overflow-hidden rounded-xl bg-white p-3 shadow-md"
        >
          <img
            v-if="!qrisImageError"
            :src="qrisImageUrl"
            alt="QRIS Payment"
            class="mx-auto max-h-64 w-auto"
            @error="qrisImageError = true"
          />
          <div
            v-else
            class="flex h-48 w-48 items-center justify-center bg-gray-100 text-gray-400"
          >
            <span class="text-sm">Gagal memuat QRIS</span>
          </div>
        </div>
      </div>

      <!-- Gift Confirmation Form -->
      <div
        class="mb-6 rounded-xl bg-white/90 p-5 shadow-md backdrop-blur-sm"
        data-aos="fade-up"
      >
        <h3 class="mb-4 text-sm font-semibold text-gray-800">
          Konfirmasi Hadiah
        </h3>

        <div v-if="giftSent" class="py-4 text-center">
          <p class="text-sm text-green-600">
            Konfirmasi hadiah Anda telah terkirim! 🎁
          </p>
        </div>

        <form
          v-else
          class="space-y-3 text-left"
          @submit.prevent="handleGiftSubmit"
        >
          <div>
            <input
              v-model="giftForm.senderName"
              type="text"
              placeholder="Nama pengirim"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            />
          </div>
          <div>
            <input
              v-model.number="giftForm.amount"
              type="number"
              placeholder="Jumlah (Rp)"
              min="0"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            />
          </div>
          <div>
            <select
              v-model="giftForm.method"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            >
              <option value="">Pilih metode pembayaran</option>
              <option value="transfer_bank">Transfer Bank</option>
              <option value="qris">QRIS</option>
              <option value="e_wallet">E-Wallet</option>
            </select>
          </div>
          <div>
            <textarea
              v-model="giftForm.message"
              placeholder="Pesan (opsional)"
              rows="2"
              class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            ></textarea>
          </div>
          <button
            type="submit"
            class="w-full rounded-lg px-4 py-2.5 text-sm font-medium text-white transition-colors hover:opacity-90 disabled:opacity-50"
            :style="{ backgroundColor: primaryColor }"
            :disabled="giftSubmitting"
          >
            {{ giftSubmitting ? "Mengirim..." : "Konfirmasi Hadiah" }}
          </button>
        </form>
      </div>

      <!-- Shipping Address -->
      <div
        v-if="shippingAddress"
        class="mb-6 rounded-xl bg-white/90 p-5 shadow-md backdrop-blur-sm"
        data-aos="fade-up"
      >
        <h3 class="mb-2 text-sm font-semibold text-gray-800">
          📦 Alamat Pengiriman Hadiah
        </h3>
        <p class="text-sm text-gray-600 whitespace-pre-line">
          {{ shippingAddress }}
        </p>
      </div>

      <!-- Live Streaming Link -->
      <div v-if="liveStreamUrl" class="mb-6" data-aos="fade-up">
        <a
          :href="liveStreamUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-medium text-white transition-colors hover:opacity-90"
          :style="{ backgroundColor: primaryColor }"
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
              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
            />
          </svg>
          Tonton Live Streaming
        </a>
      </div>

      <!-- Health Protocol -->
      <div
        v-if="healthProtocol"
        class="rounded-xl bg-blue-50 p-5"
        data-aos="fade-up"
      >
        <h3 class="mb-2 text-sm font-semibold text-blue-800">
          🏥 Protokol Kesehatan
        </h3>
        <p class="text-sm text-blue-700 whitespace-pre-line">
          {{ healthProtocol }}
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import { useClipboard } from "@/Composables/useClipboard.js";
import { paymentService } from "@/api/services/paymentService.js";

const props = defineProps({
  bankAccounts: {
    type: Array,
    default: () => [],
  },
  qrisImageUrl: {
    type: String,
    default: "",
  },
  shippingAddress: {
    type: String,
    default: "",
  },
  liveStreamUrl: {
    type: String,
    default: "",
  },
  healthProtocol: {
    type: String,
    default: "",
  },
  settings: {
    type: Object,
    default: () => ({}),
  },
  themeColors: {
    type: Object,
    default: () => ({}),
  },
});

const primaryColor = computed(() => props.themeColors?.primary || "#8B4513");

// Image error state
const qrisImageError = ref(false);

// Clipboard
const { copy } = useClipboard();
const copiedAccountId = ref(null);
let copiedTimeout = null;

async function copyAccountNumber(account) {
  if (!account.accountNumber) return;
  const success = await copy(account.accountNumber);
  if (success) {
    if (copiedTimeout) clearTimeout(copiedTimeout);
    copiedAccountId.value = account.id;
    copiedTimeout = setTimeout(() => {
      copiedAccountId.value = null;
    }, 2000);
  }
}

// Gift confirmation form
const giftForm = reactive({
  senderName: "",
  amount: null,
  method: "",
  message: "",
});
const giftSubmitting = ref(false);
const giftSent = ref(false);

async function handleGiftSubmit() {
  if (!giftForm.senderName || !giftForm.senderName.trim()) return;

  giftSubmitting.value = true;
  try {
    await paymentService.addGift({
      senderName: giftForm.senderName.trim(),
      amount: giftForm.amount || 0,
      method: giftForm.method || "transfer_bank",
      message: giftForm.message?.trim() || "",
    });
    giftSent.value = true;
  } catch {
    // Silent error handling for guest-facing app
  } finally {
    giftSubmitting.value = false;
  }
}
</script>
