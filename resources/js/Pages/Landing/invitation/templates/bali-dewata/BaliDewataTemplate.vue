<template>
  <div class="bali-dewata-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <!-- COVER SECTION with Bali decorations -->
      <section class="cover-section bali-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">
        <!-- Animated Kori Agung (Bali Gate) silhouette top -->
        <div class="bali-gate-top" aria-hidden="true">
          <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="bali-gate-svg">
            <g class="gate-body" fill="var(--bali-primary)" opacity="0.85">
              <!-- Gate pillars -->
              <rect x="30" y="60" width="30" height="140" rx="4"/>
              <rect x="340" y="60" width="30" height="140" rx="4"/>
              <!-- Gate arch top -->
              <path d="M30 80 Q200 0 370 80 L370 60 Q200-20 30 60 Z"/>
              <!-- Ornamental steps on pillars -->
              <rect x="20" y="55" width="50" height="10" rx="3"/>
              <rect x="330" y="55" width="50" height="10" rx="3"/>
              <rect x="10" y="45" width="70" height="10" rx="3"/>
              <rect x="320" y="45" width="70" height="10" rx="3"/>
              <!-- Central ornament -->
              <path d="M185 35 L200 10 L215 35 Q200 30 185 35 Z"/>
              <circle cx="200" cy="10" r="8"/>
            </g>
          </svg>
        </div>

        <!-- Floating lotus flowers -->
        <div class="bali-lotus-left floating-slow" aria-hidden="true">
          <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" width="80" height="80">
            <g fill="none" stroke="var(--bali-accent)" stroke-width="1.5" opacity="0.7">
              <ellipse cx="40" cy="50" rx="12" ry="20" transform="rotate(-20 40 50)"/>
              <ellipse cx="40" cy="50" rx="12" ry="20" transform="rotate(20 40 50)"/>
              <ellipse cx="40" cy="50" rx="12" ry="20" transform="rotate(60 40 50)"/>
              <ellipse cx="40" cy="50" rx="12" ry="20" transform="rotate(-60 40 50)"/>
              <ellipse cx="40" cy="50" rx="12" ry="20"/>
              <circle cx="40" cy="38" r="6" fill="var(--bali-accent)" opacity="0.5"/>
            </g>
          </svg>
        </div>
        <div class="bali-lotus-right floating-slow" style="animation-delay: -2s;" aria-hidden="true">
          <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" width="60" height="60">
            <g fill="none" stroke="var(--bali-accent)" stroke-width="1.5" opacity="0.6">
              <ellipse cx="40" cy="50" rx="12" ry="20" transform="rotate(-20 40 50)"/>
              <ellipse cx="40" cy="50" rx="12" ry="20" transform="rotate(20 40 50)"/>
              <ellipse cx="40" cy="50" rx="12" ry="20" transform="rotate(60 40 50)"/>
              <ellipse cx="40" cy="50" rx="12" ry="20" transform="rotate(-60 40 50)"/>
              <ellipse cx="40" cy="50" rx="12" ry="20"/>
              <circle cx="40" cy="38" r="6" fill="var(--bali-accent)" opacity="0.5"/>
            </g>
          </svg>
        </div>

        <!-- Rangda/Barong leaf ornaments -->
        <div class="bali-leaves-left" aria-hidden="true">
          <svg viewBox="0 0 60 200" xmlns="http://www.w3.org/2000/svg" width="60" height="200">
            <g fill="var(--bali-primary)" opacity="0.15">
              <path d="M50 10 Q10 40 20 80 Q5 50 50 10 Z"/>
              <path d="M50 60 Q5 90 15 130 Q0 100 50 60 Z"/>
              <path d="M50 110 Q5 140 15 180 Q0 150 50 110 Z"/>
            </g>
          </svg>
        </div>
        <div class="bali-leaves-right" aria-hidden="true">
          <svg viewBox="0 0 60 200" xmlns="http://www.w3.org/2000/svg" width="60" height="200">
            <g fill="var(--bali-primary)" opacity="0.15">
              <path d="M10 10 Q50 40 40 80 Q55 50 10 10 Z"/>
              <path d="M10 60 Q55 90 45 130 Q60 100 10 60 Z"/>
              <path d="M10 110 Q55 140 45 180 Q60 150 10 110 Z"/>
            </g>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <p class="bali-subtitle mb-2 text-sm uppercase tracking-[0.4em] opacity-75">
            ᬉᬦ᭄ᬤᬗᬦ᭄ ᬧᬾᬃᬦᬶᬓᬳᬦ᭄
          </p>
          <p class="mb-2 text-xs uppercase tracking-[0.3em] opacity-60">Undangan Pernikahan</p>
          <h1 class="bali-title mb-4 text-4xl font-bold leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-5xl md:inline md:text-7xl bali-ampersand">&</span>
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
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold tracking-wider uppercase shadow-lg cursor-pointer"
              @click="openInvitation"
            >
              <span>💌 Buka Undangan</span>
            </button>
          </div>
        </div>

        <!-- Bottom kris/wavy border -->
        <div class="bali-bottom-border" aria-hidden="true">
          <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,30 C120,60 240,0 360,30 C480,60 600,0 720,30 C840,60 960,0 1080,30 C1200,60 1320,0 1440,30 L1440,60 L0,60 Z" fill="var(--bali-primary)" opacity="0.15"/>
          </svg>
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
        <p class="mt-2 text-lg font-bold" :style="{ color: primaryColor }">
          {{ groomName }} &amp; {{ brideName }}
        </p>
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#8B1A1A");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#C41E3A");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#FFD700");

const themeStyles = computed(() => ({
  "--bali-primary": primaryColor.value,
  "--bali-secondary": secondaryColor.value,
  "--bali-accent": accentColor.value,
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
    const next = document.querySelector(".bali-cover")?.nextElementSibling;
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
