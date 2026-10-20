<template>
  <div class="invitation-container min-h-screen text-slate-800" :style="customStyles">
    <!-- Cover Section -->
    <section class="min-h-screen flex flex-col justify-between items-center p-6 text-center bg-slate-50 relative animate-in fade-in duration-500">
      <div class="pt-20 space-y-4">
        <span class="text-xs uppercase tracking-widest text-slate-500">The Wedding of</span>
        <h1 class="text-4xl md:text-6xl font-serif text-slate-900" :style="{ fontFamily: settings?.seoMeta?.fontFamily }">
          {{ couple?.groom?.nickname || 'Groom' }} & {{ couple?.bride?.nickname || 'Bride' }}
        </h1>
        <p class="text-sm text-slate-600">Kami Mengundang Anda ke Acara Pernikahan Kami</p>
      </div>

      <div class="pb-10 space-y-2">
        <p class="text-xs text-slate-500">Kepada Yth. Bapak/Ibu/Saudara/i</p>
        <div class="bg-white/80 backdrop-blur-sm px-6 py-2.5 rounded-lg border shadow-sm font-semibold">
          {{ guestName || 'Nama Tamu Undangan' }}
        </div>
        <button @click="openInvitation" class="mt-4 bg-slate-900 text-white text-xs px-6 py-2.5 rounded-full font-bold uppercase tracking-wider hover:bg-slate-800 transition">
          Buka Undangan
        </button>
      </div>
    </section>

    <!-- Content Sections (Only visible after open) -->
    <div v-if="isOpen" class="animate-fade-in">
      <!-- Couple Details -->
      <section class="py-20 px-6 text-center space-y-8 bg-white">
        <h2 class="text-2xl font-serif">Mempelai</h2>
        <div class="grid md:grid-cols-2 gap-10 max-w-4xl mx-auto">
          <div class="space-y-3">
            <h3 class="text-xl font-bold">{{ couple?.groom?.fullName || 'Nama Lengkap Groom' }}</h3>
            <p class="text-xs text-slate-500">Putra dari Ayah {{ couple?.groom?.fatherName || '...' }} & Ibu {{ couple?.groom?.motherName || '...' }}</p>
          </div>
          <div class="space-y-3">
            <h3 class="text-xl font-bold">{{ couple?.bride?.fullName || 'Nama Lengkap Bride' }}</h3>
            <p class="text-xs text-slate-500">Putri dari Ayah {{ couple?.bride?.fatherName || '...' }} & Ibu {{ couple?.bride?.motherName || '...' }}</p>
          </div>
        </div>
      </section>

      <!-- Events Details -->
      <section class="py-20 px-6 bg-slate-50 text-center space-y-8">
        <h2 class="text-2xl font-serif">Acara</h2>
        <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
          <div v-for="event in events" :key="event.id" class="bg-white p-6 rounded-xl border shadow-sm space-y-4">
            <h3 class="text-lg font-bold text-slate-900">{{ event.name }}</h3>
            <div class="text-sm text-slate-600 space-y-1">
              <p>📅 {{ event.date }}</p>
              <p>⏰ {{ event.startTime }} - {{ event.endTime || 'Selesai' }}</p>
              <p>📍 {{ event.location }}</p>
              <p class="text-xs mt-2 italic text-slate-500">{{ event.address }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Wishes & RSVP -->
      <section class="py-20 px-6 bg-white text-center space-y-8 max-w-2xl mx-auto">
        <h2 class="text-2xl font-serif">RSVP & Ucapan</h2>
        <p class="text-sm text-slate-600">Mohon konfirmasi kehadiran Anda melalui formulir di bawah ini.</p>
        <div class="p-6 border rounded-xl bg-slate-50/50 space-y-2">
          <p class="text-xs text-slate-400">RSVP & Wishes Forms are managed dynamically by the system.</p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  couple: { type: Object, default: () => ({}) },
  events: { type: Array, default: () => [] },
  settings: { type: Object, default: () => ({}) },
  media: { type: Array, default: () => [] },
  slug: { type: String, default: "" },
  templateRecord: { type: Object, default: null }
});

const isOpen = ref(false);
const guestName = ref("");

// Read guest name from URL query parameter
const urlParams = new URLSearchParams(window.location.search);
guestName.value = urlParams.get("to") || "";

function openInvitation() {
  isOpen.value = true;
  // Scroll to content
  setTimeout(() => {
    window.scrollTo({
      top: window.innerHeight,
      behavior: "smooth"
    });
  }, 100);
}

// Compute theme colors dynamically
const customStyles = computed(() => {
  const cfg = props.templateRecord?.config || {};
  return {
    '--primary-color': cfg.primary_color || '#1B3A5C',
    '--secondary-color': cfg.secondary_color || '#2E6B9E',
    '--accent-color': cfg.accent_color || '#C9A84C',
  };
});
</script>

<style scoped>
.invitation-container {
  min-height: 100vh;
}
.animate-fade-in {
  animation: fadeIn 1s ease-in-out forwards;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>