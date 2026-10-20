<template>
    <div
        class="whatsapp-link-generator rounded-lg border border-gray-200 bg-white p-5"
    >
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                Generator Link WhatsApp
            </h2>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
                :disabled="!guests.length"
                @click="handleBulkGenerate"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"
                    />
                </svg>
                Generate Semua
            </button>
        </div>

        <p v-if="!guests.length" class="text-sm text-gray-500 py-4 text-center">
            Tidak ada tamu untuk di-generate link-nya.
        </p>

        <!-- Bulk generated links -->
        <div v-if="bulkLinks.length" class="space-y-3">
            <div
                v-for="link in bulkLinks"
                :key="link.guestId"
                class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-md border border-gray-100 bg-gray-50 p-3"
            >
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        {{ getGuestName(link.guestId) }}
                    </p>
                    <p
                        class="text-xs text-gray-500 truncate mt-0.5"
                        :title="link.invitationUrl"
                    >
                        {{ link.invitationUrl }}
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
                        :aria-label="`Salin link undangan untuk ${getGuestName(link.guestId)}`"
                        @click="handleCopy(link.invitationUrl, link.guestId)"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                            />
                        </svg>
                        {{
                            copiedGuestId === link.guestId
                                ? "Tersalin!"
                                : "Salin"
                        }}
                    </button>

                    <a
                        :href="link.whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
                        :aria-label="`Buka WhatsApp untuk ${getGuestName(link.guestId)}`"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"
                            />
                            <path
                                d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.952 11.952 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.387 0-4.594-.822-6.34-2.2l-.442-.362-3.262 1.093 1.093-3.262-.362-.442A9.956 9.956 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"
                            />
                        </svg>
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- Single guest link generation (when no bulk generated yet) -->
        <div v-else-if="guests.length" class="space-y-3">
            <div
                v-for="guest in guests"
                :key="guest.id"
                class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-md border border-gray-100 bg-gray-50 p-3"
            >
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        {{ guest.name }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ guest.phone || "Tidak ada nomor telepon" }}
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
                        :aria-label="`Generate dan salin link untuk ${guest.name}`"
                        @click="handleGenerateAndCopy(guest)"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                            />
                        </svg>
                        {{
                            copiedGuestId === guest.id
                                ? "Tersalin!"
                                : "Generate Link"
                        }}
                    </button>

                    <a
                        v-if="guest.phone"
                        :href="generateSingleWhatsAppUrl(guest)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
                        :aria-label="`Buka WhatsApp untuk ${guest.name}`"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"
                            />
                            <path
                                d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.952 11.952 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.387 0-4.594-.822-6.34-2.2l-.442-.362-3.262 1.093 1.093-3.262-.362-.442A9.956 9.956 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"
                            />
                        </svg>
                        WhatsApp
                    </a>
                    <span
                        v-else
                        class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-400"
                        title="Nomor telepon diperlukan untuk WhatsApp"
                    >
                        No HP kosong
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useWhatsAppLink } from "@/Composables/useWhatsAppLink.js";
import { useClipboard } from "@/Composables/useClipboard.js";

const props = defineProps({
    guests: {
        type: Array,
        default: () => [],
    },
    domain: {
        type: String,
        default: "",
    },
    weddingSlug: {
        type: String,
        default: "",
    },
});

const { generateInvitationUrl, generateWhatsAppUrl, generateBulkLinks } =
    useWhatsAppLink({
        domain: props.domain,
        weddingSlug: props.weddingSlug,
    });

const { copy } = useClipboard();

const bulkLinks = ref([]);
const copiedGuestId = ref(null);

let copiedTimeout = null;

function getGuestName(guestId) {
    const guest = props.guests.find((g) => g.id === guestId);
    return guest ? guest.name : guestId;
}

function handleBulkGenerate() {
    bulkLinks.value = generateBulkLinks(props.guests);
}

async function handleCopy(url, guestId) {
    if (copiedTimeout) {
        clearTimeout(copiedTimeout);
    }
    await copy(url);
    copiedGuestId.value = guestId;
    copiedTimeout = setTimeout(() => {
        copiedGuestId.value = null;
        copiedTimeout = null;
    }, 2000);
}

async function handleGenerateAndCopy(guest) {
    const url = generateInvitationUrl(guest.name);
    await handleCopy(url, guest.id);
}

function generateSingleWhatsAppUrl(guest) {
    return generateWhatsAppUrl(guest.phone, guest.name);
}
</script>
