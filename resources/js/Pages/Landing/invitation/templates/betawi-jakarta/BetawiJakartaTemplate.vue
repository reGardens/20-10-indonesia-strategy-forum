<template>
  <div class="betawi-jakarta-template" :style="themeStyles">
    <PasswordGate
      v-if="settings?.passwordProtected && !authenticated"
      :password="settings.password"
      @authenticated="authenticated = true"
    />
    <template v-else>
      <MusicPlayer :src="couple?.musicUrl" />

      <!-- COVER SECTION - Betawi festive with Ondel-ondel -->
      <section class="cover-section betawi-cover relative flex flex-col items-center justify-center px-4 text-center overflow-hidden">

        <!-- Decorated umbrella top (Payung Geulis) -->
        <div class="betawi-umbrella-top" aria-hidden="true">
          <svg viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg" width="180" height="120">
            <g>
              <!-- Handle -->
              <line x1="100" y1="150" x2="100" y2="70" stroke="var(--betawi-primary)" stroke-width="4"/>
              <!-- Canopy -->
              <path d="M30,70 Q100,20 170,70 Z" fill="var(--betawi-accent)" opacity="0.8"/>
              <path d="M50,70 Q100,30 150,70 Z" fill="var(--betawi-primary)" opacity="0.7"/>
              <!-- Fringe -->
              <g stroke="var(--betawi-blue)" stroke-width="1.5" opacity="0.7">
                <line x1="30" y1="70" x2="22" y2="85"/>
                <line x1="45" y1="73" x2="40" y2="88"/>
                <line x1="60" y1="75" x2="57" y2="90"/>
                <line x1="80" y1="77" x2="78" y2="92"/>
                <line x1="100" y1="78" x2="100" y2="93"/>
                <line x1="120" y1="77" x2="122" y2="92"/>
                <line x1="140" y1="75" x2="143" y2="90"/>
                <line x1="155" y1="73" x2="160" y2="88"/>
                <line x1="170" y1="70" x2="178" y2="85"/>
              </g>
            </g>
          </svg>
        </div>

        <!-- Ondel-ondel left -->
        <div class="betawi-ondel-left ondel-bounce" aria-hidden="true">
          <svg viewBox="0 0 70 180" xmlns="http://www.w3.org/2000/svg" width="60" height="160">
            <g>
              <!-- Body (red dress) -->
              <path d="M20,80 Q10,120 15,170 L55,170 Q60,120 50,80 Z" fill="var(--betawi-primary)" opacity="0.75"/>
              <!-- Head/face -->
              <circle cx="35" cy="50" r="28" fill="#FFCC88" opacity="0.8"/>
              <!-- Hair/crown elaborate -->
              <path d="M12,38 Q35,5 58,38 Q50,25 35,22 Q20,25 12,38 Z" fill="var(--betawi-accent)" opacity="0.8"/>
              <circle cx="35" cy="10" r="8" fill="var(--betawi-primary)" opacity="0.7"/>
              <!-- Eyes -->
              <circle cx="26" cy="52" r="4" fill="#1A0505"/>
              <circle cx="44" cy="52" r="4" fill="#1A0505"/>
              <!-- Nose/mouth simplified -->
              <circle cx="35" cy="60" r="3" fill="#CC6644" opacity="0.6"/>
              <!-- Arms -->
              <path d="M20,90 Q5,105 3,125" stroke="var(--betawi-primary)" stroke-width="8" fill="none" stroke-linecap="round" opacity="0.75"/>
              <path d="M50,90 Q65,105 67,125" stroke="var(--betawi-primary)" stroke-width="8" fill="none" stroke-linecap="round" opacity="0.75"/>
            </g>
          </svg>
        </div>

        <!-- Ondel-ondel right (female, blue) -->
        <div class="betawi-ondel-right ondel-bounce" aria-hidden="true">
          <svg viewBox="0 0 70 180" xmlns="http://www.w3.org/2000/svg" width="60" height="160">
            <g>
              <path d="M20,80 Q10,120 15,170 L55,170 Q60,120 50,80 Z" fill="var(--betawi-blue)" opacity="0.75"/>
              <circle cx="35" cy="50" r="28" fill="#FFCC88" opacity="0.8"/>
              <path d="M12,38 Q35,5 58,38 Q50,25 35,22 Q20,25 12,38 Z" fill="var(--betawi-blue)" opacity="0.8"/>
              <circle cx="35" cy="10" r="8" fill="var(--betawi-accent)" opacity="0.8"/>
              <circle cx="26" cy="52" r="4" fill="#1A0505"/>
              <circle cx="44" cy="52" r="4" fill="#1A0505"/>
              <circle cx="35" cy="60" r="3" fill="#CC6644" opacity="0.6"/>
              <path d="M20,90 Q5,105 3,125" stroke="var(--betawi-blue)" stroke-width="8" fill="none" stroke-linecap="round" opacity="0.75"/>
              <path d="M50,90 Q65,105 67,125" stroke="var(--betawi-blue)" stroke-width="8" fill="none" stroke-linecap="round" opacity="0.75"/>
            </g>
          </svg>
        </div>

        <!-- Lanterns -->
        <div class="betawi-lantern-left color-pulse" aria-hidden="true">
          <svg viewBox="0 0 30 50" xmlns="http://www.w3.org/2000/svg" width="28" height="45">
            <line x1="15" y1="0" x2="15" y2="8" stroke="var(--betawi-primary)" stroke-width="2"/>
            <rect x="5" y="8" width="20" height="28" rx="5" fill="var(--betawi-primary)" opacity="0.8"/>
            <rect x="7" y="10" width="16" height="24" rx="4" fill="var(--betawi-accent)" opacity="0.5"/>
            <line x1="15" y1="36" x2="15" y2="45" stroke="var(--betawi-primary)" stroke-width="2"/>
          </svg>
        </div>
        <div class="betawi-lantern-right color-pulse" aria-hidden="true">
          <svg viewBox="0 0 30 50" xmlns="http://www.w3.org/2000/svg" width="28" height="45">
            <line x1="15" y1="0" x2="15" y2="8" stroke="var(--betawi-blue)" stroke-width="2"/>
            <rect x="5" y="8" width="20" height="28" rx="5" fill="var(--betawi-blue)" opacity="0.8"/>
            <rect x="7" y="10" width="16" height="24" rx="4" fill="var(--betawi-accent)" opacity="0.5"/>
            <line x1="15" y1="36" x2="15" y2="45" stroke="var(--betawi-blue)" stroke-width="2"/>
          </svg>
        </div>

        <!-- Text content -->
        <div class="relative z-10">
          <p class="mb-1 text-xs uppercase tracking-[0.4em] opacity-70" style="color: var(--betawi-blue);">Betawi Jakarta</p>
          <p class="mb-4 text-xs uppercase tracking-[0.3em] opacity-50">Undangan Pernikahan</p>
          <h1 class="betawi-title mb-4 text-4xl leading-tight md:text-6xl">
            {{ groomDisplayName }}
            <span class="mx-2 block md:inline betawi-ampersand">&</span>
            {{ brideDisplayName }}
          </h1>
          <template v-if="guestName">
            <p class="mt-6 text-sm opacity-80">Kepada Yth:</p>
            <p class="text-lg font-semibold">{{ guestName }}</p>
          </template>
          <template v-else>
            <p class="mt-6 text-sm opacity-70">Anda diundang untuk hadir di hari bahagia kami</p>
          </template>
          <div class="mt-8 relative z-20">
            <button
              type="button"
              class="buka-undangan-btn inline-flex items-center gap-2 px-8 py-3.5 tracking-wider uppercase cursor-pointer"
              @click="openInvitation"
            >
              <span>🎭 Buka Undangan</span>
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
        <p class="text-sm opacity-60">Terima kasih atas kehadiran dan doa restu Anda</p>
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
const primaryColor = computed(() => props.settings?.themeColors?.primary || "#CC2200");
const secondaryColor = computed(() => props.settings?.themeColors?.secondary || "#FF6633");
const accentColor = computed(() => props.settings?.themeColors?.accent || "#FFD100");
const themeStyles = computed(() => ({
  "--betawi-primary": primaryColor.value,
  "--betawi-secondary": secondaryColor.value,
  "--betawi-accent": accentColor.value,
  "--betawi-blue": "#0055AA",
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
    const next = document.querySelector(".betawi-cover")?.nextElementSibling;
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
