<template>
  <div class="melayu-riau-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <section class="cover-section melayu-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Masjid Riau silhouette -->
        <div class="melayu-masjid" aria-hidden="true">
          <svg viewBox="0 0 400 250" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
            <g fill="var(--melayu-gold)" opacity="0.25">
              <!-- Central dome -->
              <path d="M160,140 Q200,80 240,140 Z"/>
              <rect x="170" y="140" width="60" height="80" rx="2"/>
              <!-- Side minarets -->
              <rect x="80" y="120" width="20" height="100" rx="3"/>
              <path d="M80,120 Q90,100 100,120 Z"/>
              <circle cx="90" cy="96" r="6"/>
              <rect x="300" y="120" width="20" height="100" rx="3"/>
              <path d="M300,120 Q310,100 320,120 Z"/>
              <circle cx="310" cy="96" r="6"/>
              <!-- Side domes -->
              <path d="M80,140 Q100,115 120,140 Z"/>
              <path d="M280,140 Q300,115 320,140 Z"/>
              <!-- Crescent on top -->
              <path d="M196,78 A6,6 0 0,1 204,78 A5,5 0 0,0 196,78 Z"/>
              <path d="M204,72 L205,78 L204,84" stroke="var(--melayu-gold)" stroke-width="1.5" fill="none"/>
            </g>
          </svg>
        </div>

        <!-- Crescent and Star top -->
        <div class="melayu-crescent melayu-float" aria-hidden="true">
          <svg viewBox="0 0 80 60" xmlns="http://www.w3.org/2000/svg" width="80" height="60">
            <path d="M25,30 A20,20 0 0,1 55,30 A15,15 0 0,0 25,30 Z" fill="var(--melayu-gold)" opacity="0.7"/>
            <polygon points="65,10 67,18 75,18 69,23 71,31 65,26 59,31 61,23 55,18 63,18" fill="var(--melayu-gold)" opacity="0.7"/>
          </svg>
        </div>

        <!-- Songket star ornaments -->
        <div class="melayu-star-left gold-shimmer" aria-hidden="true">
          <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" width="55" height="55">
            <g fill="var(--melayu-gold)" opacity="0.5">
              <polygon points="30,2 34,20 52,14 40,28 56,36 38,36 36,56 28,40 10,50 22,34 4,28 20,22 16,4 30,16"/>
            </g>
          </svg>
        </div>
        <div class="melayu-star-right gold-shimmer" aria-hidden="true">
          <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" width="45" height="45">
            <g fill="var(--melayu-gold)" opacity="0.4">
              <polygon points="30,2 34,20 52,14 40,28 56,36 38,36 36,56 28,40 10,50 22,34 4,28 20,22 16,4 30,16"/>
            </g>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <p class="melayu-arabic mb-1 text-base opacity-70" style="color: var(--melayu-gold); font-size: 1.3rem;">بِسْمِ اللهِ</p>
          <p class="mb-2 text-xs uppercase tracking-[0.4em] opacity-60" style="color: var(--melayu-gold);">Melayu Riau</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-50" style="color: #f0ffe8;">Undangan Pernikahan</p>
          <h1 class="melayu-title mb-4 text-4xl font-bold leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-4xl md:inline melayu-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-70" style="color: #f0ffe8;">Kepada Yth:</p>
            <p class="text-lg font-semibold" style="color: #f0ffe8;">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-70" style="color: #f0ffe8;">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold tracking-wider uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>🌙 Buka Undangan</span>
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
        <p class="text-sm opacity-60">Terima kasih atas kehadiran dan doa restu Anda</p>
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#1A4A2E");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#2D7A4F");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#D4AF37");
const themeStyles = computed(() => ({
  "--melayu-primary": primaryColor.value,
  "--melayu-secondary": secondaryColor.value,
  "--melayu-accent": accentColor.value,
  "--melayu-gold": "#D4AF37",
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
    const next = document.querySelector(".melayu-cover")?.nextElementSibling;
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
