<template>
  <div class="batik-elegance-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />

    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <!-- COVER with deep purple luxury mandala -->
      <section class="cover-section batik-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Rotating mandala background -->
        <div class="batik-mandala-center" aria-hidden="true">
          <svg viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg" class="batik-spin" style="width:500px;height:500px;">
            <g stroke="var(--batik-accent)" stroke-width="0.8" fill="none" opacity="0.6">
              <circle cx="250" cy="250" r="230"/>
              <circle cx="250" cy="250" r="200"/>
              <circle cx="250" cy="250" r="160"/>
              <circle cx="250" cy="250" r="120"/>
              <circle cx="250" cy="250" r="80"/>
              <!-- Spokes -->
              <g transform="rotate(0 250 250)"><line x1="250" y1="20" x2="250" y2="480"/></g>
              <g transform="rotate(30 250 250)"><line x1="250" y1="20" x2="250" y2="480"/></g>
              <g transform="rotate(60 250 250)"><line x1="250" y1="20" x2="250" y2="480"/></g>
              <g transform="rotate(90 250 250)"><line x1="250" y1="20" x2="250" y2="480"/></g>
              <g transform="rotate(120 250 250)"><line x1="250" y1="20" x2="250" y2="480"/></g>
              <g transform="rotate(150 250 250)"><line x1="250" y1="20" x2="250" y2="480"/></g>
              <!-- Petal shapes -->
              <ellipse cx="250" cy="140" rx="25" ry="50" opacity="0.5" fill="var(--batik-accent)"/>
              <ellipse cx="250" cy="140" rx="25" ry="50" opacity="0.5" fill="var(--batik-accent)" transform="rotate(60 250 250)"/>
              <ellipse cx="250" cy="140" rx="25" ry="50" opacity="0.5" fill="var(--batik-accent)" transform="rotate(120 250 250)"/>
              <ellipse cx="250" cy="140" rx="25" ry="50" opacity="0.5" fill="var(--batik-accent)" transform="rotate(180 250 250)"/>
              <ellipse cx="250" cy="140" rx="25" ry="50" opacity="0.5" fill="var(--batik-accent)" transform="rotate(240 250 250)"/>
              <ellipse cx="250" cy="140" rx="25" ry="50" opacity="0.5" fill="var(--batik-accent)" transform="rotate(300 250 250)"/>
            </g>
          </svg>
        </div>

        <!-- Corner diamond ornaments -->
        <div class="batik-diamond-tl batik-pulse" aria-hidden="true">
          <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" width="80" height="80">
            <g fill="none" stroke="var(--batik-accent)" stroke-width="1" opacity="0.5">
              <polygon points="40,5 75,40 40,75 5,40"/>
              <polygon points="40,20 60,40 40,60 20,40"/>
              <circle cx="40" cy="40" r="8" fill="var(--batik-accent)" opacity="0.3"/>
            </g>
          </svg>
        </div>
        <div class="batik-diamond-tr batik-pulse" style="animation-delay: -1.5s;" aria-hidden="true">
          <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" width="80" height="80">
            <g fill="none" stroke="var(--batik-accent)" stroke-width="1" opacity="0.5">
              <polygon points="40,5 75,40 40,75 5,40"/>
              <polygon points="40,20 60,40 40,60 20,40"/>
              <circle cx="40" cy="40" r="8" fill="var(--batik-accent)" opacity="0.3"/>
            </g>
          </svg>
        </div>
        <div class="batik-diamond-bl batik-pulse" style="animation-delay: -0.75s;" aria-hidden="true">
          <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" width="60" height="60">
            <g fill="none" stroke="var(--batik-accent)" stroke-width="1" opacity="0.4">
              <polygon points="30,5 55,30 30,55 5,30"/>
              <circle cx="30" cy="30" r="6" fill="var(--batik-accent)" opacity="0.2"/>
            </g>
          </svg>
        </div>
        <div class="batik-diamond-br batik-pulse" style="animation-delay: -2.25s;" aria-hidden="true">
          <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" width="60" height="60">
            <g fill="none" stroke="var(--batik-accent)" stroke-width="1" opacity="0.4">
              <polygon points="30,5 55,30 30,55 5,30"/>
              <circle cx="30" cy="30" r="6" fill="var(--batik-accent)" opacity="0.2"/>
            </g>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <p class="mb-2 text-xs uppercase tracking-[0.6em] opacity-50" style="color: var(--batik-accent);">Batik Elegance</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-30" style="color: #f5f0ff;">Undangan Pernikahan</p>
          <h1 class="batik-title mb-4 text-4xl font-semibold leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-5xl md:inline batik-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-60" style="color: #f5f0ff;">Kepada Yth:</p>
            <p class="text-lg font-medium" style="color: #f5f0ff;">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-60" style="color: #f5f0ff;">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-10 py-4 text-sm font-medium tracking-widest uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>✦ Buka Undangan ✦</span>
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
      <footer class="px-4 py-8 text-center" data-aos="fade-up" style="color: #f0ecff;">
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
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#3D1A78");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#C9A64A");
const themeStyles = computed(() => ({
  "--batik-primary": primaryColor.value,
  "--batik-secondary": secondaryColor.value,
  "--batik-accent": accentColor.value,
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
    const next = document.querySelector(".batik-cover")?.nextElementSibling;
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
