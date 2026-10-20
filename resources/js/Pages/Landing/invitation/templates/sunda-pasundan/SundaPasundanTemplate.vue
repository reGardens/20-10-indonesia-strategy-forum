<template>
  <div class="sunda-pasundan-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <!-- COVER SECTION with Sunda Pasundan nature theme -->
      <section class="cover-section sunda-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Gunung (Mountain) silhouette background -->
        <div class="sunda-mountains" aria-hidden="true">
          <svg viewBox="0 0 1440 400" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMax slice">
            <g opacity="0.12">
              <polygon points="200,400 500,100 800,400" fill="var(--sunda-primary)"/>
              <polygon points="400,400 700,150 1000,400" fill="var(--sunda-secondary)"/>
              <polygon points="700,400 1050,80 1400,400" fill="var(--sunda-primary)"/>
              <!-- Snow caps -->
              <polygon points="500,100 480,155 520,155" fill="white" opacity="0.5"/>
              <polygon points="1050,80 1030,140 1070,140" fill="white" opacity="0.5"/>
            </g>
          </svg>
        </div>

        <!-- Rice field terraces bottom -->
        <div class="sunda-terraces" aria-hidden="true">
          <svg viewBox="0 0 1440 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,80 Q180,60 360,80 Q540,100 720,80 Q900,60 1080,80 Q1260,100 1440,80 L1440,120 L0,120 Z" fill="var(--sunda-secondary)" opacity="0.2"/>
            <path d="M0,95 Q180,75 360,95 Q540,115 720,95 Q900,75 1080,95 Q1260,115 1440,95 L1440,120 L0,120 Z" fill="var(--sunda-primary)" opacity="0.15"/>
          </svg>
        </div>

        <!-- Floating tropical leaves -->
        <div class="sunda-leaf sunda-leaf-1 leaf-float" aria-hidden="true">
          <svg viewBox="0 0 60 100" xmlns="http://www.w3.org/2000/svg" width="50" height="80">
            <path d="M30,5 Q55,30 50,60 Q45,85 30,95 Q15,85 10,60 Q5,30 30,5 Z" fill="var(--sunda-primary)" opacity="0.25"/>
            <line x1="30" y1="5" x2="30" y2="95" stroke="var(--sunda-secondary)" stroke-width="1" opacity="0.3"/>
            <line x1="30" y1="30" x2="15" y2="50" stroke="var(--sunda-secondary)" stroke-width="0.7" opacity="0.3"/>
            <line x1="30" y1="30" x2="45" y2="50" stroke="var(--sunda-secondary)" stroke-width="0.7" opacity="0.3"/>
            <line x1="30" y1="50" x2="18" y2="65" stroke="var(--sunda-secondary)" stroke-width="0.7" opacity="0.3"/>
            <line x1="30" y1="50" x2="42" y2="65" stroke="var(--sunda-secondary)" stroke-width="0.7" opacity="0.3"/>
          </svg>
        </div>
        <div class="sunda-leaf sunda-leaf-2 leaf-float" style="animation-delay: -2s;" aria-hidden="true">
          <svg viewBox="0 0 60 100" xmlns="http://www.w3.org/2000/svg" width="40" height="65">
            <path d="M30,5 Q55,30 50,60 Q45,85 30,95 Q15,85 10,60 Q5,30 30,5 Z" fill="var(--sunda-accent)" opacity="0.2"/>
            <line x1="30" y1="5" x2="30" y2="95" stroke="var(--sunda-primary)" stroke-width="1" opacity="0.2"/>
          </svg>
        </div>
        <div class="sunda-leaf sunda-leaf-3 leaf-float" style="animation-delay: -4s;" aria-hidden="true">
          <svg viewBox="0 0 60 100" xmlns="http://www.w3.org/2000/svg" width="45" height="70">
            <path d="M30,5 Q55,30 50,60 Q45,85 30,95 Q15,85 10,60 Q5,30 30,5 Z" fill="var(--sunda-primary)" opacity="0.2"/>
          </svg>
        </div>

        <!-- Kujang (traditional Sunda weapon/symbol) -->
        <div class="sunda-kujang-left" aria-hidden="true">
          <svg viewBox="0 0 40 120" xmlns="http://www.w3.org/2000/svg" width="40" height="120">
            <g fill="var(--sunda-accent)" opacity="0.2">
              <!-- Kujang blade stylized -->
              <path d="M20,5 Q35,20 30,50 Q35,60 30,80 Q25,90 20,100 Q15,90 10,80 Q5,60 10,50 Q5,20 20,5 Z"/>
              <circle cx="20" cy="40" r="4"/>
            </g>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <p class="sunda-script mb-1 text-base opacity-80">ᮞᮥᮔ᮪ᮓ ᮕᮞᮥᮔ᮪ᮓᮔ᮪</p>
          <p class="mb-2 text-xs uppercase tracking-[0.35em] opacity-60 font-medium">Sunda Pasundan</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-50">Undangan Pernikahan</p>
          <h1 class="sunda-title mb-4 text-4xl font-bold leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-4xl md:inline sunda-ampersand">&</span>
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
              <span>🌿 Buka Undangan</span>
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#2D5016");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#4A7C2E");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#C5A028");
const themeStyles = computed(() => ({
  "--sunda-primary": primaryColor.value,
  "--sunda-secondary": secondaryColor.value,
  "--sunda-accent": accentColor.value,
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
    const next = document.querySelector(".sunda-cover")?.nextElementSibling;
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
