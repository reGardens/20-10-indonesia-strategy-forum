<template>
  <div class="dayak-borneo-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <!-- COVER SECTION with Dayak tribal forest theme -->
      <section class="cover-section dayak-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Tribal border top -->
        <div class="dayak-tribal-top" aria-hidden="true">
          <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <defs>
              <pattern id="tribal" x="0" y="0" width="80" height="60" patternUnits="userSpaceOnUse">
                <path d="M0,30 L20,10 L40,30 L60,10 L80,30 L80,60 L0,60 Z" fill="var(--dayak-accent)" opacity="0.3"/>
                <circle cx="20" cy="10" r="3" fill="var(--dayak-accent)" opacity="0.5"/>
                <circle cx="60" cy="10" r="3" fill="var(--dayak-accent)" opacity="0.5"/>
              </pattern>
            </defs>
            <rect width="1440" height="60" fill="url(#tribal)"/>
          </svg>
        </div>

        <!-- Enggang (Hornbill bird) silhouette left -->
        <div class="dayak-hornbill-left dayak-float" aria-hidden="true">
          <svg viewBox="0 0 80 120" xmlns="http://www.w3.org/2000/svg" width="60" height="90">
            <g fill="var(--dayak-accent)" opacity="0.35">
              <!-- Body -->
              <ellipse cx="40" cy="70" rx="18" ry="25"/>
              <!-- Head -->
              <circle cx="40" cy="40" r="14"/>
              <!-- Casque/Helm (hornbill's beak) -->
              <path d="M40,30 Q60,25 65,35 Q70,45 55,50 Q50,48 46,45 L40,40 Z"/>
              <!-- Tail feathers -->
              <path d="M35,95 Q30,110 20,115 Q25,105 35,100"/>
              <path d="M40,96 Q40,112 35,118 Q38,108 40,103"/>
              <path d="M45,95 Q50,110 60,115 Q55,105 45,100"/>
              <!-- Wing -->
              <path d="M22,65 Q10,60 5,75 Q15,70 22,72"/>
              <path d="M58,65 Q70,60 75,75 Q65,70 58,72"/>
            </g>
          </svg>
        </div>
        <div class="dayak-hornbill-right dayak-float" style="animation-delay: -2s;" aria-hidden="true">
          <svg viewBox="0 0 80 120" xmlns="http://www.w3.org/2000/svg" width="60" height="90">
            <g fill="var(--dayak-accent)" opacity="0.35" transform="scale(-1,1) translate(-80,0)">
              <ellipse cx="40" cy="70" rx="18" ry="25"/>
              <circle cx="40" cy="40" r="14"/>
              <path d="M40,30 Q60,25 65,35 Q70,45 55,50 Q50,48 46,45 L40,40 Z"/>
              <path d="M35,95 Q30,110 20,115 Q25,105 35,100"/>
              <path d="M40,96 Q40,112 35,118 Q38,108 40,103"/>
              <path d="M45,95 Q50,110 60,115 Q55,105 45,100"/>
              <path d="M22,65 Q10,60 5,75 Q15,70 22,72"/>
              <path d="M58,65 Q70,60 75,75 Q65,70 58,72"/>
            </g>
          </svg>
        </div>

        <!-- Fire/torch elements bottom -->
        <div class="dayak-fire-bottom dayak-glow" aria-hidden="true">
          <svg viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg" width="200" height="80">
            <!-- Left torch -->
            <path d="M50,80 L45,60 Q50,40 55,50 Q60,30 65,40 Q70,20 75,35 Q80,50 75,60 Z" fill="var(--dayak-accent)" opacity="0.6"/>
            <path d="M55,80 L52,65 Q57,48 60,55 Q63,42 67,50 Q70,60 67,70 Z" fill="#FF8C00" opacity="0.5"/>
            <rect x="48" y="77" width="14" height="3" rx="1" fill="var(--dayak-secondary)"/>
            <!-- Right torch -->
            <path d="M125,80 L120,60 Q125,40 130,50 Q135,30 140,40 Q145,20 150,35 Q155,50 150,60 Z" fill="var(--dayak-accent)" opacity="0.6"/>
            <path d="M130,80 L127,65 Q132,48 135,55 Q138,42 142,50 Q145,60 142,70 Z" fill="#FF8C00" opacity="0.5"/>
            <rect x="123" y="77" width="14" height="3" rx="1" fill="var(--dayak-secondary)"/>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <div class="dayak-divider mb-3" aria-hidden="true">
            <svg viewBox="0 0 300 16" xmlns="http://www.w3.org/2000/svg" width="220" height="12">
              <line x1="0" y1="8" x2="100" y2="8" stroke="var(--dayak-accent)" stroke-width="1" opacity="0.5"/>
              <path d="M130,2 L150,8 L130,14 L110,8 Z" fill="var(--dayak-accent)" opacity="0.5"/>
              <line x1="200" y1="8" x2="300" y2="8" stroke="var(--dayak-accent)" stroke-width="1" opacity="0.5"/>
            </svg>
          </div>
          <p class="mb-2 text-xs uppercase tracking-[0.5em] opacity-60" style="color: var(--dayak-accent);">Dayak Borneo</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-40" style="color: #f5e8c0;">Undangan Pernikahan</p>
          <h1 class="dayak-title mb-4 text-4xl font-bold leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-5xl md:inline dayak-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-70" style="color: #f5e8c0;">Kepada Yth:</p>
            <p class="text-lg font-semibold" style="color: #f5e8c0;">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-70" style="color: #f5e8c0;">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold tracking-wider uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>🔥 Buka Undangan</span>
            </button>
          </div>
          <div class="dayak-divider mt-4" aria-hidden="true">
            <svg viewBox="0 0 300 16" xmlns="http://www.w3.org/2000/svg" width="220" height="12">
              <line x1="0" y1="8" x2="100" y2="8" stroke="var(--dayak-accent)" stroke-width="1" opacity="0.5"/>
              <path d="M130,2 L150,8 L130,14 L110,8 Z" fill="var(--dayak-accent)" opacity="0.5"/>
              <line x1="200" y1="8" x2="300" y2="8" stroke="var(--dayak-accent)" stroke-width="1" opacity="0.5"/>
            </svg>
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
      <footer class="px-4 py-8 text-center" data-aos="fade-up" style="color: #f0e0c0;">
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#2C1A0E");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#8B4A1A");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#E8A020");
const themeStyles = computed(() => ({
  "--dayak-primary": primaryColor.value,
  "--dayak-secondary": secondaryColor.value,
  "--dayak-accent": accentColor.value,
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
    const next = document.querySelector(".dayak-cover")?.nextElementSibling;
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
