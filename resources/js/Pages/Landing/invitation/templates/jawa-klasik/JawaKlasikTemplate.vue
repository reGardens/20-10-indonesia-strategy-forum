<template>
  <div class="jawa-klasik-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <!-- COVER SECTION with Jawa Klasik decorations -->
      <section class="cover-section jawa-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Pendopo Joglo roof silhouette -->
        <div class="joglo-roof" aria-hidden="true">
          <svg viewBox="0 0 500 180" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
            <g fill="var(--jawa-primary)" opacity="0.8">
              <!-- Top finial -->
              <polygon points="250,5 260,30 240,30"/>
              <rect x="243" y="25" width="14" height="10" rx="2"/>
              <!-- Main roof peak -->
              <polygon points="250,35 380,110 120,110"/>
              <!-- Lower eave -->
              <polygon points="250,80 420,130 80,130"/>
              <!-- Decorative ridge ornaments -->
              <circle cx="250" cy="30" r="5"/>
              <rect x="190" y="108" width="20" height="6" rx="2"/>
              <rect x="290" y="108" width="20" height="6" rx="2"/>
            </g>
          </svg>
        </div>

        <!-- Batik corner decorations -->
        <div class="batik-corner batik-tl" aria-hidden="true">
          <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" width="100" height="100">
            <g stroke="var(--jawa-primary)" stroke-width="1" fill="none" opacity="0.3">
              <path d="M0,0 Q50,0 100,100"/>
              <path d="M0,20 Q40,20 80,100"/>
              <path d="M20,0 Q20,40 100,80"/>
              <!-- Batik diamond motif -->
              <rect x="20" y="20" width="20" height="20" transform="rotate(45 30 30)" fill="var(--jawa-accent)" opacity="0.3"/>
              <circle cx="30" cy="30" r="5" fill="var(--jawa-primary)" opacity="0.4"/>
            </g>
          </svg>
        </div>
        <div class="batik-corner batik-tr" aria-hidden="true">
          <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" width="100" height="100">
            <g stroke="var(--jawa-primary)" stroke-width="1" fill="none" opacity="0.3" transform="scale(-1,1) translate(-100,0)">
              <path d="M0,0 Q50,0 100,100"/>
              <path d="M0,20 Q40,20 80,100"/>
              <path d="M20,0 Q20,40 100,80"/>
              <rect x="20" y="20" width="20" height="20" transform="rotate(45 30 30)" fill="var(--jawa-accent)" opacity="0.3"/>
              <circle cx="30" cy="30" r="5" fill="var(--jawa-primary)" opacity="0.4"/>
            </g>
          </svg>
        </div>

        <!-- Floating wayang puppet silhouette -->
        <div class="wayang-left floating-gentle" aria-hidden="true">
          <svg viewBox="0 0 50 120" xmlns="http://www.w3.org/2000/svg" width="50" height="120">
            <g fill="var(--jawa-primary)" opacity="0.2">
              <!-- Body -->
              <ellipse cx="25" cy="30" rx="10" ry="12"/>
              <!-- Head -->
              <circle cx="25" cy="14" r="9"/>
              <!-- Crown -->
              <polygon points="25,2 18,10 22,8 25,4 28,8 32,10"/>
              <!-- Arms -->
              <path d="M15,35 Q5,45 2,60 Q8,50 15,42"/>
              <path d="M35,35 Q45,45 48,60 Q42,50 35,42"/>
              <!-- Legs/skirt -->
              <path d="M18,45 Q15,70 12,90 Q20,75 25,55 Q30,75 38,90 Q35,70 32,45 Z"/>
            </g>
          </svg>
        </div>
        <div class="wayang-right floating-gentle" style="animation-delay: -3s;" aria-hidden="true">
          <svg viewBox="0 0 50 120" xmlns="http://www.w3.org/2000/svg" width="50" height="120">
            <g fill="var(--jawa-primary)" opacity="0.2" transform="scale(-1,1) translate(-50,0)">
              <ellipse cx="25" cy="30" rx="10" ry="12"/>
              <circle cx="25" cy="14" r="9"/>
              <polygon points="25,2 18,10 22,8 25,4 28,8 32,10"/>
              <path d="M15,35 Q5,45 2,60 Q8,50 15,42"/>
              <path d="M35,35 Q45,45 48,60 Q42,50 35,42"/>
              <path d="M18,45 Q15,70 12,90 Q20,75 25,55 Q30,75 38,90 Q35,70 32,45 Z"/>
            </g>
          </svg>
        </div>

        <!-- Batik floral pattern bottom -->
        <div class="batik-bottom-pattern" aria-hidden="true">
          <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <defs>
              <pattern id="batik" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
                <circle cx="30" cy="30" r="12" fill="none" stroke="var(--jawa-primary)" stroke-width="1" opacity="0.25"/>
                <circle cx="30" cy="30" r="6" fill="var(--jawa-accent)" opacity="0.15"/>
                <circle cx="0" cy="0" r="6" fill="none" stroke="var(--jawa-primary)" stroke-width="1" opacity="0.2"/>
                <circle cx="60" cy="0" r="6" fill="none" stroke="var(--jawa-primary)" stroke-width="1" opacity="0.2"/>
                <circle cx="0" cy="60" r="6" fill="none" stroke="var(--jawa-primary)" stroke-width="1" opacity="0.2"/>
                <circle cx="60" cy="60" r="6" fill="none" stroke="var(--jawa-primary)" stroke-width="1" opacity="0.2"/>
              </pattern>
            </defs>
            <rect width="1440" height="80" fill="url(#batik)"/>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <div class="jawa-ornament-line mb-2" aria-hidden="true">
            <svg viewBox="0 0 300 20" xmlns="http://www.w3.org/2000/svg" width="200" height="14">
              <line x1="0" y1="10" x2="120" y2="10" stroke="var(--jawa-accent)" stroke-width="1" opacity="0.6"/>
              <circle cx="150" cy="10" r="6" fill="var(--jawa-accent)" opacity="0.6"/>
              <line x1="180" y1="10" x2="300" y2="10" stroke="var(--jawa-accent)" stroke-width="1" opacity="0.6"/>
            </svg>
          </div>
          <p class="mb-2 text-xs uppercase tracking-[0.4em] opacity-70 font-semibold">Undangan Pernikahan</p>
          <h1 class="jawa-title mb-4 text-4xl font-bold leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-5xl md:inline md:text-7xl jawa-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <div class="jawa-ornament-line mt-2 mb-4" aria-hidden="true">
            <svg viewBox="0 0 300 20" xmlns="http://www.w3.org/2000/svg" width="200" height="14">
              <line x1="0" y1="10" x2="120" y2="10" stroke="var(--jawa-primary)" stroke-width="1" opacity="0.4"/>
              <rect x="137" y="4" width="26" height="12" rx="2" fill="var(--jawa-primary)" opacity="0.3"/>
              <line x1="180" y1="10" x2="300" y2="10" stroke="var(--jawa-primary)" stroke-width="1" opacity="0.4"/>
            </svg>
          </div>
          <template v-if="guestName">
            <p class="mt-4 text-sm opacity-70">Kepada Yth:</p>
            <p class="text-lg font-semibold">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-4 text-sm opacity-70">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold tracking-wider uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>💌 Buka Undangan</span>
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#8B4513");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#D2691E");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#FFD700");
const themeStyles = computed(() => ({
  "--jawa-primary": primaryColor.value,
  "--jawa-secondary": secondaryColor.value,
  "--jawa-accent": accentColor.value,
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
    const next = document.querySelector(".jawa-cover")?.nextElementSibling;
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
