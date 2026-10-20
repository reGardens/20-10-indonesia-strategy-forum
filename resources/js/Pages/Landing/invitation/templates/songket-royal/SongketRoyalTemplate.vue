<template>
  <div class="songket-royal-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <section class="cover-section songket-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Royal crown -->
        <div class="songket-crown crown-bob" aria-hidden="true">
          <svg viewBox="0 0 200 120" xmlns="http://www.w3.org/2000/svg" width="200" height="100">
            <g fill="var(--songket-accent)" opacity="0.85">
              <!-- Crown base -->
              <path d="M20,80 L40,30 L70,60 L100,10 L130,60 L160,30 L180,80 Z"/>
              <rect x="15" y="80" width="170" height="20" rx="5"/>
              <!-- Jewels -->
              <circle cx="100" cy="10" r="8" fill="var(--songket-silk)" opacity="0.8"/>
              <circle cx="40" cy="30" r="6" fill="#9B59B6" opacity="0.7"/>
              <circle cx="160" cy="30" r="6" fill="#2980B9" opacity="0.7"/>
              <circle cx="70" cy="60" r="5" fill="var(--songket-silk)" opacity="0.7"/>
              <circle cx="130" cy="60" r="5" fill="var(--songket-silk)" opacity="0.7"/>
            </g>
          </svg>
        </div>

        <!-- Ornamental frames left & right -->
        <div class="songket-frame-left" aria-hidden="true">
          <svg viewBox="0 0 60 400" xmlns="http://www.w3.org/2000/svg" width="60" height="100%">
            <defs>
              <pattern id="royal-frame" x="0" y="0" width="60" height="40" patternUnits="userSpaceOnUse">
                <path d="M0,20 L30,0 L60,20 L30,40 Z" fill="none" stroke="var(--songket-accent)" stroke-width="0.8" opacity="0.4"/>
                <circle cx="30" cy="20" r="4" fill="var(--songket-accent)" opacity="0.3"/>
              </pattern>
            </defs>
            <rect width="60" height="400" fill="url(#royal-frame)"/>
          </svg>
        </div>
        <div class="songket-frame-right" aria-hidden="true">
          <svg viewBox="0 0 60 400" xmlns="http://www.w3.org/2000/svg" width="60" height="100%">
            <defs>
              <pattern id="royal-frame-r" x="0" y="0" width="60" height="40" patternUnits="userSpaceOnUse">
                <path d="M0,20 L30,0 L60,20 L30,40 Z" fill="none" stroke="var(--songket-accent)" stroke-width="0.8" opacity="0.4"/>
                <circle cx="30" cy="20" r="4" fill="var(--songket-accent)" opacity="0.3"/>
              </pattern>
            </defs>
            <rect width="60" height="400" fill="url(#royal-frame-r)"/>
          </svg>
        </div>

        <!-- Bottom ornament -->
        <div class="songket-bottom-ornament" aria-hidden="true">
          <svg viewBox="0 0 600 40" xmlns="http://www.w3.org/2000/svg" width="min(600px,100%)">
            <g stroke="var(--songket-accent)" stroke-width="0.8" fill="none" opacity="0.5">
              <path d="M0,20 Q150,5 300,20 Q450,35 600,20"/>
              <circle cx="150" cy="12" r="5" fill="var(--songket-accent)" opacity="0.4"/>
              <circle cx="300" cy="20" r="7" fill="var(--songket-accent)" opacity="0.4"/>
              <circle cx="450" cy="28" r="5" fill="var(--songket-accent)" opacity="0.4"/>
            </g>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10 mt-16">
          <div class="royal-seal mb-3" aria-hidden="true">
            <svg viewBox="0 0 200 20" xmlns="http://www.w3.org/2000/svg" width="180" height="16">
              <line x1="0" y1="10" x2="75" y2="10" stroke="var(--songket-accent)" stroke-width="0.8" opacity="0.7"/>
              <polygon points="95,2 100,10 95,18 90,10" fill="var(--songket-accent)" opacity="0.6"/>
              <polygon points="105,2 110,10 105,18 100,10" fill="var(--songket-accent)" opacity="0.4"/>
              <line x1="125" y1="10" x2="200" y2="10" stroke="var(--songket-accent)" stroke-width="0.8" opacity="0.7"/>
            </svg>
          </div>
          <p class="mb-2 text-xs uppercase tracking-[0.5em] opacity-60" style="color: var(--songket-accent);">Songket Royal</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-40" style="color: #fff8e8;">Undangan Pernikahan</p>
          <h1 class="songket-title mb-4 text-4xl leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-5xl md:inline songket-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-70" style="color: #fff8e8;">Kepada Yth:</p>
            <p class="text-lg" style="color: var(--songket-accent);">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-70" style="color: #fff8e8;">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-10 py-4 text-xs font-semibold tracking-widest uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>👑 BUKA UNDANGAN</span>
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
      <footer class="px-4 py-8 text-center" data-aos="fade-up" style="color: #fff8e8;">
        <p class="text-sm opacity-60">Terima kasih atas kehadiran dan doa restu Anda</p>
        <p class="mt-2 text-lg font-bold uppercase tracking-wide" :style="{ color: accentColor }">{{ groomName }} &amp; {{ brideName }}</p>
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#2A0A00");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#7A1C00");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#FFB800");
const themeStyles = computed(() => ({
  "--songket-primary": primaryColor.value,
  "--songket-secondary": secondaryColor.value,
  "--songket-accent": accentColor.value,
  "--songket-gold": "#DAA520",
  "--songket-silk": "#8B0000",
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
    const next = document.querySelector(".songket-cover")?.nextElementSibling;
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
