<template>
  <div class="invitation-layout w-full overflow-x-hidden">
    <router-view />

    <!-- Powered by watermark, controlled by settings.showWatermark -->
    <div v-if="showWatermark" class="invitation-watermark py-4 text-center">
      <p class="text-xs text-gray-400 opacity-60">Powered by RBP</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import AOS from "aos";
import "aos/dist/aos.css";
import { settingsService } from "@/api/services/settingsService.js";

const showWatermark = ref(false);

// ─── Scroll State ────────────────────────────────────────────────────────────
let isScrolling = false;
let scrollCooldown = false;     // extra guard: after any scroll, block briefly
let lastScrollTime = 0;
let touchStartY = 0;
const SCROLL_COOLDOWN_MS = 900; // how long to wait before allowing next section jump
const TOUCH_THRESHOLD_PX = 60; // min swipe distance

// ─── Helpers ─────────────────────────────────────────────────────────────────
function getSections() {
  return Array.from(
    document.querySelectorAll(".invitation-layout section, .invitation-layout footer")
  );
}

function getClosestSectionIndex(elements) {
  let closestIndex = 0;
  let minDiff = Infinity;
  elements.forEach((el, index) => {
    const rect = el.getBoundingClientRect();
    // Use the absolute distance from the top of viewport
    const diff = Math.abs(rect.top);
    if (diff < minDiff) {
      minDiff = diff;
      closestIndex = index;
    }
  });
  return closestIndex;
}

function scrollToSection(elements, index) {
  if (index < 0 || index >= elements.length) return;
  if (isScrolling || scrollCooldown) return;

  isScrolling = true;
  scrollCooldown = true;
  lastScrollTime = Date.now();

  const targetEl = elements[index];
  const rect = targetEl.getBoundingClientRect();
  const scrollTop = window.scrollY || document.documentElement.scrollTop;
  const targetY = rect.top + scrollTop;

  window.scrollTo({ top: targetY, behavior: "smooth" });

  // Release isScrolling after animation completes
  setTimeout(() => {
    isScrolling = false;
  }, 800);

  // Release cooldown after a longer delay to prevent rapid multi-section jumps
  setTimeout(() => {
    scrollCooldown = false;
  }, SCROLL_COOLDOWN_MS);
}

// ─── Wheel Handler ────────────────────────────────────────────────────────────
// Accumulate delta to distinguish "flick" vs "slow scroll"
let wheelAccumulator = 0;
let wheelResetTimer = null;
const WHEEL_THRESHOLD = 50; // accumulated deltaY before triggering section jump

function handleWheel(e) {
  // Always block scroll when locked (cover page is showing)
  if (document.documentElement.classList.contains("invitation-locked")) {
    e.preventDefault();
    return;
  }

  // Block new section jump while one is in progress
  if (isScrolling || scrollCooldown) {
    e.preventDefault();
    return;
  }

  e.preventDefault(); // always prevent default — we handle scrolling entirely

  // Accumulate wheel delta (handles trackpad momentum)
  wheelAccumulator += e.deltaY;

  // Reset accumulator after idle
  clearTimeout(wheelResetTimer);
  wheelResetTimer = setTimeout(() => {
    wheelAccumulator = 0;
  }, 150);

  // Only trigger when accumulated enough
  if (Math.abs(wheelAccumulator) < WHEEL_THRESHOLD) return;

  const direction = wheelAccumulator > 0 ? 1 : -1;
  wheelAccumulator = 0; // reset after triggering

  const elements = getSections();
  if (elements.length === 0) return;

  const currentIndex = getClosestSectionIndex(elements);
  const nextIndex = currentIndex + direction;

  if (nextIndex >= 0 && nextIndex < elements.length) {
    scrollToSection(elements, nextIndex);
  }
}

// ─── Touch Handlers ───────────────────────────────────────────────────────────
function handleTouchStart(e) {
  if (e.touches.length > 0) {
    touchStartY = e.touches[0].clientY;
  }
}

function handleTouchMove(e) {
  // Block scrolling when locked or animating
  if (
    document.documentElement.classList.contains("invitation-locked") ||
    isScrolling ||
    scrollCooldown
  ) {
    e.preventDefault();
  }
}

function handleTouchEnd(e) {
  if (
    document.documentElement.classList.contains("invitation-locked") ||
    isScrolling ||
    scrollCooldown
  ) {
    return;
  }

  if (e.changedTouches.length === 0) return;

  const touchEndY = e.changedTouches[0].clientY;
  const diffY = touchStartY - touchEndY;

  if (Math.abs(diffY) < TOUCH_THRESHOLD_PX) return; // too small — ignore

  const direction = diffY > 0 ? 1 : -1;
  const elements = getSections();
  if (elements.length === 0) return;

  const currentIndex = getClosestSectionIndex(elements);
  const nextIndex = currentIndex + direction;

  if (nextIndex >= 0 && nextIndex < elements.length) {
    scrollToSection(elements, nextIndex);
  }
}

// ─── Keyboard Handler ─────────────────────────────────────────────────────────
function handleKeyDown(e) {
  if (document.documentElement.classList.contains("invitation-locked")) {
    if (["ArrowDown", "ArrowUp", "PageDown", "PageUp", " "].includes(e.key)) {
      e.preventDefault();
    }
    return;
  }

  if (isScrolling || scrollCooldown) {
    e.preventDefault();
    return;
  }

  let direction = 0;
  if (e.key === "ArrowDown" || e.key === "PageDown" || e.key === " ") direction = 1;
  else if (e.key === "ArrowUp" || e.key === "PageUp") direction = -1;
  else return;

  e.preventDefault();

  const elements = getSections();
  if (elements.length === 0) return;

  const currentIndex = getClosestSectionIndex(elements);
  const nextIndex = currentIndex + direction;

  if (nextIndex >= 0 && nextIndex < elements.length) {
    scrollToSection(elements, nextIndex);
  }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
  document.documentElement.classList.add("hide-scrollbar");
  document.body.classList.add("hide-scrollbar");

  window.addEventListener("wheel", handleWheel, { passive: false });
  window.addEventListener("touchstart", handleTouchStart, { passive: true });
  window.addEventListener("touchmove", handleTouchMove, { passive: false });
  window.addEventListener("touchend", handleTouchEnd, { passive: true });
  window.addEventListener("keydown", handleKeyDown, { passive: false });

  AOS.init({
    duration: 800,
    easing: "ease-in-out",
    once: true,
    offset: 50,
    delay: 0,
  });

  try {
    const settings = await settingsService.get();
    showWatermark.value =
      settings?.showWatermark !== undefined ? settings.showWatermark : true;
  } catch {
    showWatermark.value = true;
  }
});

onUnmounted(() => {
  document.documentElement.classList.remove("hide-scrollbar");
  document.body.classList.remove("hide-scrollbar");

  window.removeEventListener("wheel", handleWheel);
  window.removeEventListener("touchstart", handleTouchStart);
  window.removeEventListener("touchmove", handleTouchMove);
  window.removeEventListener("touchend", handleTouchEnd);
  window.removeEventListener("keydown", handleKeyDown);
});
</script>

<style scoped>
.invitation-layout {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.invitation-watermark {
  pointer-events: none;
  user-select: none;
}
</style>
