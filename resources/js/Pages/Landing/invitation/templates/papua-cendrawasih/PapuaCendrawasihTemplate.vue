<template>
  <div class="papua-cendrawasih-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <section class="cover-section papua-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Cendrawasih bird (Bird of Paradise) -->
        <div class="papua-cendrawasih-bird" aria-hidden="true">
          <svg viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg" width="200" height="140">
            <g>
              <!-- Body -->
              <ellipse cx="100" cy="70" rx="35" ry="25" fill="var(--papua-accent)" opacity="0.6"/>
              <!-- Head -->
              <circle cx="130" cy="55" r="18" fill="var(--papua-accent)" opacity="0.7"/>
              <!-- Eye -->
              <circle cx="137" cy="52" r="4" fill="#1A0A2E"/>
              <circle cx="138" cy="51" r="1.5" fill="white"/>
              <!-- Beak -->
              <path d="M148,55 Q162,55 158,58 Q155,61 148,58 Z" fill="#1A0A2E" opacity="0.5"/>
              <!-- Tail plumage (decorative trailing feathers) -->
              <path d="M65,80 Q30,100 5,130" stroke="var(--papua-teal)" stroke-width="2.5" fill="none" opacity="0.7"/>
              <path d="M65,75 Q25,95 0,120" stroke="var(--papua-yellow)" stroke-width="2" fill="none" opacity="0.6"/>
              <path d="M65,85 Q35,105 15,140" stroke="var(--papua-accent)" stroke-width="2" fill="none" opacity="0.5"/>
              <path d="M65,78 Q28,98 8,125" stroke="var(--papua-teal)" stroke-width="1.5" fill="none" opacity="0.4"/>
              <!-- Wing -->
              <path d="M90,60 Q80,40 100,35 Q120,30 130,45 Q115,42 100,50 Z" fill="var(--papua-secondary)" opacity="0.5"/>
              <!-- Crest -->
              <path d="M130,37 Q125,20 135,15 Q140,25 140,37" fill="var(--papua-yellow)" opacity="0.6"/>
              <path d="M125,37 Q115,18 120,10 Q128,22 130,37" fill="var(--papua-teal)" opacity="0.5"/>
            </g>
          </svg>
        </div>

        <!-- Tropical leaves left -->
        <div class="papua-tropical-left tropical-glow" aria-hidden="true">
          <svg viewBox="0 0 80 200" xmlns="http://www.w3.org/2000/svg" width="70" height="180">
            <g fill="var(--papua-teal)" opacity="0.3">
              <path d="M70,20 Q10,60 25,100 Q5,70 70,20 Z"/>
              <path d="M70,80 Q10,120 25,160 Q5,130 70,80 Z"/>
              <path d="M70,140 Q10,180 25,200 Q5,185 70,140 Z"/>
            </g>
          </svg>
        </div>
        <div class="papua-tropical-right tropical-glow" aria-hidden="true">
          <svg viewBox="0 0 80 200" xmlns="http://www.w3.org/2000/svg" width="70" height="180">
            <g fill="var(--papua-accent)" opacity="0.25" transform="scale(-1,1) translate(-80,0)">
              <path d="M70,20 Q10,60 25,100 Q5,70 70,20 Z"/>
              <path d="M70,80 Q10,120 25,160 Q5,130 70,80 Z"/>
              <path d="M70,140 Q10,180 25,200 Q5,185 70,140 Z"/>
            </g>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10 mt-24">
          <p class="mb-2 text-xs uppercase tracking-[0.5em] opacity-60" style="color: var(--papua-teal);">Papua Cendrawasih</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-40" style="color: #fff0e0;">Undangan Pernikahan</p>
          <h1 class="papua-title mb-4 text-4xl font-semibold leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-5xl md:inline papua-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-70" style="color: #fff0e0;">Kepada Yth:</p>
            <p class="text-lg" style="color: #fff0e0;">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-70" style="color: #fff0e0;">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold tracking-wider uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>🦜 Buka Undangan</span>
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
      <footer class="px-4 py-8 text-center" data-aos="fade-up" style="color: #fff0e0;">
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#1A0A2E");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#4A1580");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#FF6B35");
const themeStyles = computed(() => ({
  "--papua-primary": primaryColor.value,
  "--papua-secondary": secondaryColor.value,
  "--papua-accent": accentColor.value,
  "--papua-teal": "#00C9A7",
  "--papua-yellow": "#FFD60A",
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
    const next = document.querySelector(".papua-cover")?.nextElementSibling;
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
