<template>
  <div class="minang-rantau-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <!-- COVER SECTION with Minang decorations (Rumah Gadang) -->
      <section class="cover-section minang-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Rumah Gadang (Minangkabau traditional house) silhouette -->
        <div class="rumah-gadang" aria-hidden="true">
          <svg viewBox="0 0 500 250" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
            <g fill="var(--minang-primary)" opacity="0.75">
              <!-- Main roof with curved buffalo horns shape -->
              <path d="M80,200 L80,130 Q100,80 150,50 Q180,30 200,10 Q220,30 250,40 Q280,30 300,10 Q320,30 350,50 Q400,80 420,130 L420,200 Z"/>
              <!-- Second horn peak -->
              <path d="M200,10 Q220,40 250,50 Q280,40 300,10 Q280,20 250,25 Q220,20 200,10 Z" fill="var(--minang-accent)" opacity="0.6"/>
              <!-- Walls and columns -->
              <rect x="90" y="200" width="320" height="50" rx="2"/>
              <rect x="110" y="160" width="12" height="40"/>
              <rect x="380" y="160" width="12" height="40"/>
              <rect x="180" y="160" width="12" height="40"/>
              <rect x="300" y="160" width="12" height="40"/>
              <!-- Door -->
              <rect x="220" y="210" width="60" height="40" rx="4" fill="var(--minang-accent)" opacity="0.5"/>
              <rect x="244" y="210" width="12" height="40" fill="var(--minang-primary)"/>
            </g>
          </svg>
        </div>

        <!-- Songket pattern sides -->
        <div class="minang-songket-left" aria-hidden="true">
          <svg viewBox="0 0 50 300" xmlns="http://www.w3.org/2000/svg" width="50" height="300">
            <defs>
              <pattern id="songket-l" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                <rect width="20" height="20" fill="none"/>
                <path d="M0,10 L10,0 L20,10 L10,20 Z" fill="var(--minang-accent)" opacity="0.2"/>
                <circle cx="10" cy="10" r="2" fill="var(--minang-primary)" opacity="0.3"/>
              </pattern>
            </defs>
            <rect width="50" height="300" fill="url(#songket-l)"/>
          </svg>
        </div>
        <div class="minang-songket-right" aria-hidden="true">
          <svg viewBox="0 0 50 300" xmlns="http://www.w3.org/2000/svg" width="50" height="300">
            <defs>
              <pattern id="songket-r" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                <rect width="20" height="20" fill="none"/>
                <path d="M0,10 L10,0 L20,10 L10,20 Z" fill="var(--minang-accent)" opacity="0.2"/>
                <circle cx="10" cy="10" r="2" fill="var(--minang-primary)" opacity="0.3"/>
              </pattern>
            </defs>
            <rect width="50" height="300" fill="url(#songket-r)"/>
          </svg>
        </div>

        <!-- Floating bunga (flowers) -->
        <div class="minang-flower minang-flower-1 minang-float" aria-hidden="true">
          <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" width="50" height="50">
            <g fill="var(--minang-accent)" opacity="0.5">
              <ellipse cx="30" cy="15" rx="8" ry="14" transform="rotate(0 30 30)"/>
              <ellipse cx="30" cy="15" rx="8" ry="14" transform="rotate(60 30 30)"/>
              <ellipse cx="30" cy="15" rx="8" ry="14" transform="rotate(120 30 30)"/>
              <ellipse cx="30" cy="15" rx="8" ry="14" transform="rotate(180 30 30)"/>
              <ellipse cx="30" cy="15" rx="8" ry="14" transform="rotate(240 30 30)"/>
              <ellipse cx="30" cy="15" rx="8" ry="14" transform="rotate(300 30 30)"/>
              <circle cx="30" cy="30" r="8" fill="var(--minang-primary)"/>
            </g>
          </svg>
        </div>
        <div class="minang-flower minang-flower-2 minang-float" style="animation-delay: -2.5s;" aria-hidden="true">
          <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" width="35" height="35">
            <g fill="var(--minang-primary)" opacity="0.3">
              <ellipse cx="30" cy="15" rx="7" ry="13" transform="rotate(0 30 30)"/>
              <ellipse cx="30" cy="15" rx="7" ry="13" transform="rotate(60 30 30)"/>
              <ellipse cx="30" cy="15" rx="7" ry="13" transform="rotate(120 30 30)"/>
              <ellipse cx="30" cy="15" rx="7" ry="13" transform="rotate(180 30 30)"/>
              <ellipse cx="30" cy="15" rx="7" ry="13" transform="rotate(240 30 30)"/>
              <ellipse cx="30" cy="15" rx="7" ry="13" transform="rotate(300 30 30)"/>
              <circle cx="30" cy="30" r="7" fill="var(--minang-accent)"/>
            </g>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <p class="mb-1 text-xs uppercase tracking-[0.5em] opacity-60">Minangkabau</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-50">Undangan Pernikahan</p>
          <h1 class="minang-title mb-4 text-4xl font-bold leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-4xl md:inline minang-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-70">Kepada Yth:</p>
            <p class="text-lg font-semibold">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-70">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold tracking-wider uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>🏠 Buka Undangan</span>
            </button>
          </div>
        </div>
      </section>

      <CoupleSection :couple="couple" :theme-colors="settings?.themeColors" />
      <EventSection :events="events" :theme-colors="settings?.themeColors" />
      <CountdownSection :target-date="firstEventDate" :theme-colors="settings?.themeColors" />
      <GallerySection :media="media" :gallery-layout="settings?.galleryLayout" :theme-colors="settings?.themeColors" />
      <RsvpSection :theme-colors="settings?.themeColors" />
      <WishesSection :wishes="wishes" :theme-colors="settings?.themeColors" />
      <GiftSection
        :bank-accounts="bankAccounts"
        :qris-image-url="settings?.qrisImageUrl"
        :shipping-address="settings?.shippingAddress"
        :live-stream-url="settings?.liveStreamUrl"
        :health-protocol="settings?.healthProtocol"
        :theme-colors="settings?.themeColors"
      />
      <footer class="px-4 py-8 text-center" data-aos="fade-up">
        <p class="text-sm text-gray-500">Terima kasih atas kehadiran dan doa restu Anda</p>
        <p class="mt-2 text-lg font-bold" :style="{ color: primaryColor }">{{ groomName }} &amp; {{ brideName }}</p>
      </footer>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from "vue";
import "./styles.css";
import PasswordGate from "@/Pages/Landing/invitation/sections/PasswordGate.vue";
import MusicPlayer from "@/Pages/Landing/invitation/sections/MusicPlayer.vue";
import CoupleSection from "@/Pages/Landing/invitation/sections/CoupleSection.vue";
import EventSection from "@/Pages/Landing/invitation/sections/EventSection.vue";
import CountdownSection from "@/Pages/Landing/invitation/sections/CountdownSection.vue";
import GallerySection from "@/Pages/Landing/invitation/sections/GallerySection.vue";
import RsvpSection from "@/Pages/Landing/invitation/sections/RsvpSection.vue";
import WishesSection from "@/Pages/Landing/invitation/sections/WishesSection.vue";
import GiftSection from "@/Pages/Landing/invitation/sections/GiftSection.vue";
import { useGuestName } from "@/Pages/Landing/invitation/composables/useGuestName.js";
import { wishService } from "@/api/services/wishService.js";
import { paymentService } from "@/api/services/paymentService.js";

const props = defineProps({
  couple: { type: Object, default: null },
  events: { type: Array, default: () => [] },
  settings: { type: Object, default: null },
  media: { type: Array, default: () => [] },
  slug: { type: String, default: "" },
});

const authenticated = ref(false);
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#7B1020");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#A51C30");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#DAA520");
const themeStyles = computed(() => ({
  "--minang-primary": primaryColor.value,
  "--minang-secondary": secondaryColor.value,
  "--minang-accent": accentColor.value,
}));
const groomName = computed(() => props.couple?.groom?.fullName || props.couple?.groom?.nickname || "Mempelai Pria");
const brideName = computed(() => props.couple?.bride?.fullName || props.couple?.bride?.nickname || "Mempelai Wanita");
const groomDisplayName = computed(() => props.couple?.groom?.nickname || props.couple?.groom?.fullName || "Mempelai Pria");
const brideDisplayName = computed(() => props.couple?.bride?.nickname || props.couple?.bride?.fullName || "Mempelai Wanita");
const { guestName } = useGuestName();
const firstEventDate = computed(() => props.events?.[0]?.date || "");
const wishes = ref([]);
const bankAccounts = ref([]);

function openInvitation() {
  document.documentElement.classList.remove("invitation-locked");
  document.body.classList.remove("invitation-locked");
  setTimeout(() => {
    const next = document.querySelector(".minang-cover")?.nextElementSibling;
    if (next) next.scrollIntoView({ behavior: "smooth" });
  }, 100);
}

onMounted(() => {
  document.documentElement.classList.add("invitation-locked");
  document.body.classList.add("invitation-locked");
});

onMounted(async () => {
  try {
    const [w, b] = await Promise.all([
      wishService.getAll(props.slug),
      paymentService.getBankAccounts(props.slug),
    ]);
    wishes.value = w;
    bankAccounts.value = b;
  } catch {}
});
</script>
