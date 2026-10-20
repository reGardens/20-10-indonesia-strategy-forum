<template>
  <div class="toraja-sulawesi-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <section class="cover-section toraja-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Pa'barre allo (Toraja sun motif) -->
        <div class="toraja-pa-barre" aria-hidden="true">
          <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" width="110" height="110">
            <g fill="var(--toraja-accent)" opacity="0.5">
              <!-- Central circle -->
              <circle cx="60" cy="60" r="20"/>
              <!-- Rays -->
              <g>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(0 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(30 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(60 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(90 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(120 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(150 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(180 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(210 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(240 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(270 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(300 60 60)"/>
                <rect x="57" y="5" width="6" height="30" rx="3" transform="rotate(330 60 60)"/>
              </g>
            </g>
          </svg>
        </div>

        <!-- Tongkonan house silhouette -->
        <div class="toraja-tongkonan tongkonan-bob" aria-hidden="true">
          <svg viewBox="0 0 500 280" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
            <g fill="var(--toraja-accent)" opacity="0.3">
              <!-- Characteristic curved roof -->
              <path d="M50,150 Q100,60 250,30 Q400,60 450,150 Q350,120 250,110 Q150,120 50,150 Z"/>
              <!-- Roof ridge ends (high curving) -->
              <path d="M50,150 Q20,140 0,160 Q30,155 50,150 Z"/>
              <path d="M450,150 Q480,140 500,160 Q470,155 450,150 Z"/>
              <!-- Main body -->
              <rect x="100" y="150" width="300" height="100" rx="5"/>
              <!-- Columns -->
              <rect x="120" y="150" width="15" height="100"/>
              <rect x="180" y="150" width="15" height="100"/>
              <rect x="305" y="150" width="15" height="100"/>
              <rect x="365" y="150" width="15" height="100"/>
              <!-- Door -->
              <rect x="210" y="190" width="80" height="60" rx="5" fill="var(--toraja-gold)"/>
              <!-- Carved panel decorations -->
              <rect x="130" y="165" width="35" height="30" rx="2" fill="var(--toraja-gold)" opacity="0.5"/>
              <rect x="175" y="165" width="35" height="30" rx="2" fill="var(--toraja-gold)" opacity="0.5"/>
              <rect x="305" y="165" width="35" height="30" rx="2" fill="var(--toraja-gold)" opacity="0.5"/>
              <rect x="350" y="165" width="35" height="30" rx="2" fill="var(--toraja-gold)" opacity="0.5"/>
            </g>
          </svg>
        </div>

        <!-- Buffalo horns left -->
        <div class="toraja-buffalo-left buffalo-horn" aria-hidden="true">
          <svg viewBox="0 0 60 100" xmlns="http://www.w3.org/2000/svg" width="50" height="80">
            <g fill="var(--toraja-gold)" opacity="0.3">
              <path d="M50,90 Q60,60 50,30 Q40,10 30,5 Q20,10 15,25 Q10,45 30,60 Q40,70 50,90 Z"/>
              <circle cx="30" cy="5" r="5"/>
            </g>
          </svg>
        </div>
        <div class="toraja-buffalo-right buffalo-horn" style="animation-delay: -2.5s;" aria-hidden="true">
          <svg viewBox="0 0 60 100" xmlns="http://www.w3.org/2000/svg" width="50" height="80">
            <g fill="var(--toraja-gold)" opacity="0.3" transform="scale(-1,1) translate(-60,0)">
              <path d="M50,90 Q60,60 50,30 Q40,10 30,5 Q20,10 15,25 Q10,45 30,60 Q40,70 50,90 Z"/>
              <circle cx="30" cy="5" r="5"/>
            </g>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10 mt-20">
          <p class="mb-2 text-xs uppercase tracking-[0.5em] opacity-60" style="color: var(--toraja-accent);">Toraja Sulawesi</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-40" style="color: #fff5e8;">Undangan Pernikahan</p>
          <h1 class="toraja-title mb-4 text-4xl leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-5xl md:inline toraja-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-70" style="color: #fff5e8;">Kepada Yth:</p>
            <p class="text-lg" style="color: #fff5e8;">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-70" style="color: #fff5e8;">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold tracking-wider uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>🦬 Buka Undangan</span>
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
      <footer class="px-4 py-8 text-center" data-aos="fade-up" style="color: #fff5e8;">
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#1A0800");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#4A1500");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#CC4400");
const themeStyles = computed(() => ({
  "--toraja-primary": primaryColor.value,
  "--toraja-secondary": secondaryColor.value,
  "--toraja-accent": accentColor.value,
  "--toraja-gold": "#B8860B",
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
    const next = document.querySelector(".toraja-cover")?.nextElementSibling;
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
