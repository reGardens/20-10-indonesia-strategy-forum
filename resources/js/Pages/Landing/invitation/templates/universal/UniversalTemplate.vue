<template>
  <div class="universal-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />

    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <section class="cover-section universal-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Morphing blob background 1 -->
        <div class="universal-blob-1" aria-hidden="true"></div>
        <div class="universal-blob-2" aria-hidden="true"></div>

        <!-- Geometric line ornament -->
        <div class="universal-line-ornament" aria-hidden="true">
          <svg viewBox="0 0 1440 2" xmlns="http://www.w3.org/2000/svg" width="100%" height="2px">
            <line x1="0" y1="1" x2="1440" y2="1" stroke="var(--uni-accent)" stroke-width="1"/>
          </svg>
        </div>

        <!-- Abstract floating rings -->
        <div class="float-circle" style="position:absolute;top:10%;right:10%;width:150px;height:150px;border-radius:50%;border:1px solid rgba(233,69,96,0.15);pointer-events:none;animation-delay:-2s;" aria-hidden="true"></div>
        <div class="float-circle" style="position:absolute;bottom:15%;left:8%;width:100px;height:100px;border-radius:50%;border:1px solid rgba(100,100,255,0.12);pointer-events:none;animation-delay:-4s;" aria-hidden="true"></div>
        <div class="float-circle" style="position:absolute;top:25%;left:5%;width:60px;height:60px;border-radius:50%;border:1px solid rgba(233,69,96,0.1);pointer-events:none;" aria-hidden="true"></div>

        <!-- Dot grid -->
        <div style="position:absolute;inset:0;pointer-events:none;overflow:hidden;" aria-hidden="true">
          <svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" preserveAspectRatio="xMidYMid slice">
            <defs>
              <pattern id="dots" x="0" y="0" width="30" height="30" patternUnits="userSpaceOnUse">
                <circle cx="15" cy="15" r="1" fill="rgba(233,69,96,0.15)"/>
              </pattern>
            </defs>
            <rect width="400" height="400" fill="url(#dots)"/>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <p class="mb-2 text-xs uppercase tracking-[0.5em] opacity-60" style="color: var(--uni-accent);">Wedding Invitation</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-30" style="color: #fff;">Undangan Pernikahan</p>
          <h1 class="universal-title mb-4 text-5xl leading-tight md:text-7xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-4xl md:inline universal-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <div class="mt-2 mb-6" aria-hidden="true">
            <svg viewBox="0 0 250 8" xmlns="http://www.w3.org/2000/svg" width="200" height="6">
              <line x1="0" y1="4" x2="100" y2="4" stroke="rgba(233,69,96,0.4)" stroke-width="1"/>
              <circle cx="125" cy="4" r="4" fill="var(--uni-accent)" opacity="0.6"/>
              <line x1="150" y1="4" x2="250" y2="4" stroke="rgba(233,69,96,0.4)" stroke-width="1"/>
            </svg>
          </div>
          <template v-if="guestName">
            <p class="text-sm opacity-60" style="color: #f0f0ff;">Kepada Yth:</p>
            <p class="text-lg font-medium" style="color: #fff;">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="text-sm opacity-60" style="color: #f0f0ff;">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-semibold tracking-wider uppercase cursor-pointer"
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
        <p class="text-sm text-gray-400">Terima kasih atas kehadiran dan doa restu Anda</p>
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#1A1A2E");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#16213E");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#E94560");
const themeStyles = computed(() => ({
  "--uni-primary": primaryColor.value,
  "--uni-secondary": secondaryColor.value,
  "--uni-accent": accentColor.value,
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
    const next = document.querySelector(".universal-cover")?.nextElementSibling;
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
