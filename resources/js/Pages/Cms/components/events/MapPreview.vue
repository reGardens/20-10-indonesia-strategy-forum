<template>
    <div
        class="map-preview rounded-lg border border-gray-200 bg-white overflow-hidden"
    >
        <div v-if="hasCoordinates" class="relative">
            <iframe
                :src="mapUrl"
                :title="mapTitle"
                class="w-full h-64 border-0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            />
        </div>
        <div
            v-else
            class="flex items-center justify-center h-64 bg-gray-50 text-gray-400"
            aria-label="Masukkan koordinat untuk melihat preview peta"
        >
            <div class="text-center">
                <svg
                    class="mx-auto h-12 w-12 text-gray-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"
                    />
                </svg>
                <p class="mt-2 text-sm">
                    Masukkan latitude dan longitude untuk melihat preview peta
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { validateCoordinate } from "@/Utils/validators";

const props = defineProps({
    latitude: {
        type: [Number, null],
        default: null,
    },
    longitude: {
        type: [Number, null],
        default: null,
    },
});

const hasCoordinates = computed(() => {
    return (
        props.latitude !== null &&
        props.longitude !== null &&
        validateCoordinate(props.latitude, "latitude") &&
        validateCoordinate(props.longitude, "longitude")
    );
});

const mapUrl = computed(() => {
    if (!hasCoordinates.value) return "";
    return `https://maps.google.com/maps?q=${props.latitude},${props.longitude}&output=embed`;
});

const mapTitle = computed(() => {
    if (!hasCoordinates.value) return "Preview Peta";
    return `Peta lokasi (${props.latitude}, ${props.longitude})`;
});
</script>
