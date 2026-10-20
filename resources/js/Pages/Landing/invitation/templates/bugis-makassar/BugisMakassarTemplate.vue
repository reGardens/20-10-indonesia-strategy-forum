<template>
  <div class="bugis-makassar-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <section class="cover-section bugis-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Stars background -->
        <div class="bugis-stars" aria-hidden="true">
          <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" width="100%" height="200px" style="position:absolute;top:0;left:0;width:100%;">
            <g fill="white">
              <circle cx="40" cy="20" r="1.5" opacity="0.6" class="star-twinkle"/>
              <circle cx="100" cy="50" r="1" opacity="0.4" class="star-twinkle" style="animation-delay:-0.5s"/>
              <circle cx="160" cy="15" r="2" opacity="0.7" class="star-twinkle" style="animation-delay:-1s"/>
              <circle cx="220" cy="40" r="1.5" opacity="0.5" class="star-twinkle" style="animation-delay:-1.5s"/>
              <circle cx="280" cy="10" r="1" opacity="0.6" class="star-twinkle" style="animation-delay:-2s"/>
              <circle cx="340" cy="35" r="2" opacity="0.5" class="star-twinkle" style="animation-delay:-0.8s"/>
              <circle cx="380" cy="18" r="1.5" opacity="0.7" class="star-twinkle" style="animation-delay:-1.3s"/>
              <circle cx="70" cy="80" r="1" opacity="0.4" class="star-twinkle" style="animation-delay:-2.5s"/>
              <circle cx="130" cy="90" r="1.5" opacity="0.6" class="star-twinkle" style="animation-delay:-0.3s"/>
              <circle cx="250" cy="70" r="1" opacity="0.5" class="star-twinkle" style="animation-delay:-1.8s"/>
              <circle cx="320" cy="85" r="2" opacity="0.4" class="star-twinkle" style="animation-delay:-0.7s"/>
              <!-- Crescent moon -->
              <path d="M200,25 A15,15 0 0,1 220,25 A12,12 0 0,0 200,25 Z" fill="var(--bugis-accent)" opacity="0.8"/>
            </g>
          </svg>
        </div>

        <!-- Phinisi ship -->
        <div class="bugis-phinisi sail-rock" aria-hidden="true">
          <svg viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg" width="280" height="160">
            <g fill="var(--bugis-accent)" opacity="0.4">
              <!-- Hull -->
              <path d="M30,140 Q150,155 270,140 L260,165 Q150,175 40,165 Z"/>
              <!-- Mast -->
              <rect x="146" y="40" width="8" height="105"/>
              <!-- Main sail -->
              <path d="M154,45 Q200,80 190,140 L154,140 Z"/>
              <!-- Front sail -->
              <path d="M146,60 Q100,90 110,135 L146,135 Z"/>
              <!-- Flag -->
              <path d="M154,40 L175,50 L154,60 Z" fill="var(--bugis-primary)" opacity="0.8"/>
              <!-- Waves -->
              <path d="M10,165 Q50,158 90,165 Q130,172 170,165 Q210,158 250,165 Q290,172 310,165" stroke="var(--bugis-sea)" stroke-width="3" fill="none" opacity="0.5"/>
            </g>
          </svg>
        </div>

        <!-- Waves bottom -->
        <div class="bugis-waves" aria-hidden="true">
          <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,40 Q180,20 360,40 Q540,60 720,40 Q900,20 1080,40 Q1260,60 1440,40 L1440,80 L0,80 Z" fill="var(--bugis-sea)" opacity="0.3"/>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <p class="mb-2 text-xs uppercase tracking-[0.5em] opacity-60" style="color: var(--bugis-accent);">Bugis Makassar</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-40" style="color: #f0f8ff;">Undangan Pernikahan</p>
          <h1 class="bugis-title mb-4 text-4xl font-bold leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-5xl md:inline bugis-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-70" style="color: #f0f8ff;">Kepada Yth:</p>
            <p class="text-lg font-semibold" style="color: #f0f8ff;">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-70" style="color: #f0f8ff;">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold tracking-wider uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>⚓ Buka Undangan</span>
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
      <footer class="px-4 py-8 text-center" data-aos="fade-up" style="color: #f0f8ff;">
        <p class="text-sm opacity-60">Terima kasih atas kehadiran dan doa restu Anda</p>
        <p class="mt-2 text-lg font-bold" :style="{ color: accentColor }">{{ groomName }} &amp; {{ brideName }}</p>
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#003366");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#0055AA");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#FFD700");
const themeStyles = computed(() => ({
  "--bugis-primary": primaryColor.value,
  "--bugis-secondary": secondaryColor.value,
  "--bugis-accent": accentColor.value,
  "--bugis-sea": "#006699",
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
    const next = document.querySelector(".bugis-cover")?.nextElementSibling;
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
