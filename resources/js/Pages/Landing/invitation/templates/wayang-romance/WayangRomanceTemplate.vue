<template>
  <div class="wayang-romance-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />

    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <section class="cover-section wayang-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Blencong (oil lamp) left & right -->
        <div class="wayang-lamp-left lamp-glow" aria-hidden="true">
          <svg viewBox="0 0 50 100" xmlns="http://www.w3.org/2000/svg" width="40" height="80">
            <g>
              <!-- Flame -->
              <path d="M25,35 Q30,20 28,10 Q26,5 25,8 Q24,5 22,10 Q20,20 25,35 Z" fill="var(--wayang-accent)" opacity="0.8"/>
              <path d="M25,35 Q27,25 26,18 Q25,14 25,18 Q24,25 25,35 Z" fill="#FFD700" opacity="0.6"/>
              <!-- Lamp body -->
              <ellipse cx="25" cy="55" rx="15" ry="20" fill="var(--wayang-gold)" opacity="0.6"/>
              <ellipse cx="25" cy="38" rx="8" ry="5" fill="var(--wayang-gold)" opacity="0.5"/>
              <!-- Stand -->
              <rect x="23" y="75" width="4" height="20" fill="var(--wayang-gold)" opacity="0.5"/>
              <rect x="15" y="93" width="20" height="4" rx="2" fill="var(--wayang-gold)" opacity="0.5"/>
            </g>
          </svg>
        </div>

        <div class="wayang-lamp-right lamp-glow" style="animation-delay: -1s;" aria-hidden="true">
          <svg viewBox="0 0 50 100" xmlns="http://www.w3.org/2000/svg" width="40" height="80">
            <g>
              <path d="M25,35 Q30,20 28,10 Q26,5 25,8 Q24,5 22,10 Q20,20 25,35 Z" fill="var(--wayang-accent)" opacity="0.8"/>
              <path d="M25,35 Q27,25 26,18 Q25,14 25,18 Q24,25 25,35 Z" fill="#FFD700" opacity="0.6"/>
              <ellipse cx="25" cy="55" rx="15" ry="20" fill="var(--wayang-gold)" opacity="0.6"/>
              <ellipse cx="25" cy="38" rx="8" ry="5" fill="var(--wayang-gold)" opacity="0.5"/>
              <rect x="23" y="75" width="4" height="20" fill="var(--wayang-gold)" opacity="0.5"/>
              <rect x="15" y="93" width="20" height="4" rx="2" fill="var(--wayang-gold)" opacity="0.5"/>
            </g>
          </svg>
        </div>

        <!-- Wayang puppet silhouettes left (Arjuna style) -->
        <div class="wayang-puppet-left wayang-float" aria-hidden="true">
          <svg viewBox="0 0 60 200" xmlns="http://www.w3.org/2000/svg" width="50" height="170">
            <g fill="var(--wayang-gold)" opacity="0.25">
              <!-- Head with crown -->
              <ellipse cx="30" cy="30" rx="14" ry="16"/>
              <path d="M16,20 Q30,2 44,20 Q38,10 30,8 Q22,10 16,20 Z"/>
              <polygon points="30,2 24,10 30,6 36,10"/>
              <!-- Body -->
              <path d="M22,46 Q15,70 18,100 L42,100 Q45,70 38,46 Z"/>
              <!-- Sarong/skirt flowing -->
              <path d="M18,100 Q10,140 5,190 L25,190 Q28,150 30,110 Q32,150 35,190 L55,190 Q50,140 42,100 Z"/>
              <!-- Arms -->
              <path d="M22,55 Q8,65 2,90 Q10,75 22,68"/>
              <path d="M38,55 Q52,65 58,90 Q50,75 38,68"/>
            </g>
          </svg>
        </div>

        <!-- Wayang puppet right (Srikandi style) -->
        <div class="wayang-puppet-right wayang-float" style="animation-delay: -3s;" aria-hidden="true">
          <svg viewBox="0 0 60 200" xmlns="http://www.w3.org/2000/svg" width="50" height="170">
            <g fill="var(--wayang-gold)" opacity="0.25" transform="scale(-1,1) translate(-60,0)">
              <ellipse cx="30" cy="30" rx="14" ry="16"/>
              <path d="M16,20 Q30,2 44,20 Q38,10 30,8 Q22,10 16,20 Z"/>
              <polygon points="30,2 24,10 30,6 36,10"/>
              <path d="M22,46 Q15,70 18,100 L42,100 Q45,70 38,46 Z"/>
              <path d="M18,100 Q10,140 5,190 L25,190 Q28,150 30,110 Q32,150 35,190 L55,190 Q50,140 42,100 Z"/>
              <path d="M22,55 Q8,65 2,90 Q10,75 22,68"/>
              <path d="M38,55 Q52,65 58,90 Q50,75 38,68"/>
            </g>
          </svg>
        </div>

        <!-- Kelir (shadow puppet screen border) bottom -->
        <div class="wayang-screen" aria-hidden="true">
          <svg viewBox="0 0 1440 40" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <rect width="1440" height="8" fill="var(--wayang-accent)" opacity="0.4"/>
            <rect y="32" width="1440" height="8" fill="var(--wayang-accent)" opacity="0.4"/>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <p class="mb-1 text-xs uppercase tracking-[0.5em] opacity-50" style="color: var(--wayang-gold);">Wayang Romance</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-35" style="color: #fff5e0;">Undangan Pernikahan</p>
          <h1 class="wayang-title mb-4 text-4xl leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block text-4xl md:inline wayang-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-70" style="color: #fff5e0;">Kepada Yth:</p>
            <p class="text-lg" style="color: var(--wayang-gold);">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-70" style="color: #fff5e0;">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-10 py-4 text-sm italic tracking-widest uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>🪔 Buka Undangan</span>
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
      <footer class="wayang-footer px-4 py-8 text-center" data-aos="fade-right" style="color: #fff5e0;">
        <p class="text-sm opacity-60">Terima kasih atas kehadiran dan doa restu Anda</p>
        <p class="mt-2 text-lg font-semibold" :style="{ color: primaryColor }">{{ groomName }} &amp; {{ brideName }}</p>
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#1A0808");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#3D0000");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#FF9500");
const themeStyles = computed(() => ({
  "--wayang-primary": primaryColor.value,
  "--wayang-secondary": secondaryColor.value,
  "--wayang-accent": accentColor.value,
  "--wayang-gold": "#E8C060",
  "--wayang-shadow": "#4A2000",
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
    const next = document.querySelector(".wayang-cover")?.nextElementSibling;
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
