<script setup>
/**
 * IsfAdBanner — Slot Iklan Spanduk (Ad Placement Banner) di Halaman Beranda.
 */
import { computed } from 'vue';

const props = defineProps({
    ad: {
        type: Object,
        default: () => ({}),
    },
    cellPad: {
        type: String,
        default: 'lg:px-4 xl:px-[30px]',
    },
});

const hasActiveAd = computed(() => {
    if (!props.ad) return false;
    if (props.ad.isActive === false || props.ad.is_active === false) return false;
    if (props.ad.status === 'draf') return false;
    return Boolean(imageUrl.value);
});

const kicker = computed(() => props.ad?.kicker || 'SPONSOR RESMI');
const title = computed(() => props.ad?.title || '');
const imageUrl = computed(() => props.ad?.imageUrl || props.ad?.image_url || '');
const targetUrl = computed(() => props.ad?.targetUrl || props.ad?.target_url || '');

const isExternal = computed(() => /^https?:\/\//i.test(targetUrl.value));
</script>

<template>
    <section
        aria-labelledby="ad-placement-title"
        class="border-t border-neutral-300"
        data-testid="ad-placement-section"
    >
        <div :class="['flex items-baseline justify-between pt-2.5 pb-1', cellPad]">
            <h2
                id="ad-placement-title"
                class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 font-space-grotesk"
            >
                {{ hasActiveAd ? kicker : 'SPONSOR RESMI' }}
            </h2>
            <span class="text-[9px] uppercase tracking-wider text-neutral-400/80 font-space-grotesk font-medium">
                Iklan
            </span>
        </div>

        <div :class="['pt-0.5 pb-3', cellPad]">
            <!-- Iklan Aktif dengan Gambar Banner -->
            <a
                v-if="hasActiveAd && imageUrl"
                :href="targetUrl || '#'"
                :target="isExternal ? '_blank' : undefined"
                :rel="isExternal ? 'noopener noreferrer' : undefined"
                class="group block relative overflow-hidden transition-opacity hover:opacity-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
                data-testid="ad-placement-banner"
            >
                <div class="relative w-full aspect-[4/3] sm:aspect-[16/10] lg:aspect-[4/3] overflow-hidden bg-neutral-100">
                    <img
                        :src="imageUrl"
                        :alt="title || 'Iklan Sponsor'"
                        class="w-full h-full object-cover group-hover:scale-[1.01] transition-transform duration-300"
                        loading="lazy"
                    />
                </div>
            </a>

            <!-- Component Default Bertuliskan ADS jika tidak ada status yang aktif -->
            <div
                v-else
                class="w-full aspect-[4/3] sm:aspect-[16/10] lg:aspect-[4/3] border border-dashed border-neutral-300 bg-neutral-100/70 rounded flex flex-col items-center justify-center p-4 text-center select-none"
                data-testid="ad-placement-default-ads"
            >
                <span class="font-anton text-3xl sm:text-4xl tracking-widest text-neutral-400/80 font-bold uppercase">
                    ADS
                </span>
                <span class="text-[10px] uppercase font-space-grotesk font-semibold text-neutral-400 tracking-wider mt-1">
                    Ruang Iklan Tersedia
                </span>
            </div>
        </div>
    </section>
</template>
