<template>
    <div class="event-form rounded-lg border border-gray-200 bg-white p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ isEditing ? "Edit Acara" : "Tambah Acara" }}
            </h2>
            <button
                type="button"
                class="text-gray-400 hover:text-gray-600 transition-colors"
                aria-label="Tutup formulir"
                @click="$emit('cancel')"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <form @submit.prevent="handleSubmit" novalidate>
            <!-- Name (required) -->
            <div class="mb-4">
                <label
                    :for="`${formId}-name`"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Nama Acara <span class="text-red-500">*</span>
                </label>
                <input
                    :id="`${formId}-name`"
                    v-model="form.name"
                    type="text"
                    class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    :class="errors.name ? 'border-red-300' : 'border-gray-300'"
                    placeholder="Contoh: Akad Nikah"
                    @blur="validateName"
                />
                <p
                    v-if="errors.name"
                    class="mt-1 text-xs text-red-600"
                    role="alert"
                >
                    {{ errors.name }}
                </p>
            </div>

            <!-- Date (required) -->
            <div class="mb-4">
                <label
                    :for="`${formId}-date`"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Tanggal <span class="text-red-500">*</span>
                </label>
                <input
                    :id="`${formId}-date`"
                    v-model="form.date"
                    type="date"
                    class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    :class="errors.date ? 'border-red-300' : 'border-gray-300'"
                    @blur="validateDate"
                    @change="validateDate"
                />
                <p
                    v-if="errors.date"
                    class="mt-1 text-xs text-red-600"
                    role="alert"
                >
                    {{ errors.date }}
                </p>
                <p
                    v-if="pastDateWarning"
                    class="mt-1 text-xs text-amber-600"
                    role="status"
                >
                    {{ pastDateWarning }}
                </p>
            </div>

            <!-- Time range -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label
                        :for="`${formId}-startTime`"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Waktu Mulai
                    </label>
                    <input
                        :id="`${formId}-startTime`"
                        v-model="form.startTime"
                        type="time"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    />
                </div>
                <div>
                    <label
                        :for="`${formId}-endTime`"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Waktu Selesai
                    </label>
                    <input
                        :id="`${formId}-endTime`"
                        v-model="form.endTime"
                        type="time"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    />
                </div>
            </div>

            <!-- Venue Name -->
            <div class="mb-4">
                <label
                    :for="`${formId}-venueName`"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Nama Tempat
                </label>
                <input
                    :id="`${formId}-venueName`"
                    v-model="form.venueName"
                    type="text"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Contoh: Masjid Istiqlal"
                />
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label
                    :for="`${formId}-address`"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Alamat
                </label>
                <textarea
                    :id="`${formId}-address`"
                    v-model="form.address"
                    rows="2"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Contoh: Jl. Taman Wijaya Kusuma, Jakarta Pusat"
                />
            </div>

            <!-- Coordinates -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label
                        :for="`${formId}-latitude`"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Latitude
                    </label>
                    <input
                        :id="`${formId}-latitude`"
                        v-model.number="form.latitude"
                        type="number"
                        step="any"
                        class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        :class="
                            errors.latitude
                                ? 'border-red-300'
                                : 'border-gray-300'
                        "
                        placeholder="-6.1702"
                        @blur="validateLatitude"
                    />
                    <p
                        v-if="errors.latitude"
                        class="mt-1 text-xs text-red-600"
                        role="alert"
                    >
                        {{ errors.latitude }}
                    </p>
                </div>
                <div>
                    <label
                        :for="`${formId}-longitude`"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Longitude
                    </label>
                    <input
                        :id="`${formId}-longitude`"
                        v-model.number="form.longitude"
                        type="number"
                        step="any"
                        class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        :class="
                            errors.longitude
                                ? 'border-red-300'
                                : 'border-gray-300'
                        "
                        placeholder="106.8310"
                        @blur="validateLongitude"
                    />
                    <p
                        v-if="errors.longitude"
                        class="mt-1 text-xs text-red-600"
                        role="alert"
                    >
                        {{ errors.longitude }}
                    </p>
                </div>
            </div>

            <!-- Map Preview -->
            <div class="mb-4">
                <MapPreview
                    :latitude="parsedLatitude"
                    :longitude="parsedLongitude"
                />
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
                    @click="$emit('cancel')"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="saving"
                >
                    <span v-if="saving">Menyimpan...</span>
                    <span v-else>{{ isEditing ? "Perbarui" : "Simpan" }}</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, computed, watch } from "vue";
import { validateCoordinate, validatePastDate } from "@/Utils/validators";
import MapPreview from "./MapPreview.vue";

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            name: "",
            date: "",
            startTime: "",
            endTime: "",
            venueName: "",
            address: "",
            latitude: "",
            longitude: "",
        }),
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    saving: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["submit", "cancel"]);

const formId = `event-form-${Math.random().toString(36).slice(2, 9)}`;

const form = reactive({
    name: props.modelValue.name || "",
    date: props.modelValue.date || "",
    startTime: props.modelValue.startTime || "",
    endTime: props.modelValue.endTime || "",
    venueName: props.modelValue.venueName || "",
    address: props.modelValue.address || "",
    latitude: props.modelValue.latitude ?? "",
    longitude: props.modelValue.longitude ?? "",
});

const errors = reactive({
    name: "",
    date: "",
    latitude: "",
    longitude: "",
});

const pastDateWarning = computed(() => {
    if (!form.date) return "";
    if (validatePastDate(form.date)) {
        return "Peringatan: Tanggal acara sudah lewat";
    }
    return "";
});

const parsedLatitude = computed(() => {
    const val = Number(form.latitude);
    return form.latitude !== "" && !Number.isNaN(val) ? val : null;
});

const parsedLongitude = computed(() => {
    const val = Number(form.longitude);
    return form.longitude !== "" && !Number.isNaN(val) ? val : null;
});

// Sync incoming prop changes to form
watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal) {
            form.name = newVal.name || "";
            form.date = newVal.date || "";
            form.startTime = newVal.startTime || "";
            form.endTime = newVal.endTime || "";
            form.venueName = newVal.venueName || "";
            form.address = newVal.address || "";
            form.latitude = newVal.latitude ?? "";
            form.longitude = newVal.longitude ?? "";
        }
    },
    { deep: true },
);

function validateName() {
    if (!form.name.trim()) {
        errors.name = "Nama acara wajib diisi";
    } else {
        errors.name = "";
    }
}

function validateDate() {
    if (!form.date) {
        errors.date = "Tanggal wajib diisi";
    } else {
        errors.date = "";
    }
}

function validateLatitude() {
    if (
        form.latitude === "" ||
        form.latitude === null ||
        form.latitude === undefined
    ) {
        errors.latitude = "";
        return;
    }
    const val = Number(form.latitude);
    if (Number.isNaN(val) || !validateCoordinate(val, "latitude")) {
        errors.latitude = "Latitude harus antara -90 dan 90";
    } else {
        errors.latitude = "";
    }
}

function validateLongitude() {
    if (
        form.longitude === "" ||
        form.longitude === null ||
        form.longitude === undefined
    ) {
        errors.longitude = "";
        return;
    }
    const val = Number(form.longitude);
    if (Number.isNaN(val) || !validateCoordinate(val, "longitude")) {
        errors.longitude = "Longitude harus antara -180 dan 180";
    } else {
        errors.longitude = "";
    }
}

function validateAll() {
    validateName();
    validateDate();
    validateLatitude();
    validateLongitude();
    return (
        !errors.name && !errors.date && !errors.latitude && !errors.longitude
    );
}

function handleSubmit() {
    if (!validateAll()) return;

    const data = {
        name: form.name.trim(),
        date: form.date,
        startTime: form.startTime || "",
        endTime: form.endTime || "",
        venueName: form.venueName || "",
        address: form.address || "",
        latitude: parsedLatitude.value,
        longitude: parsedLongitude.value,
    };

    emit("submit", data);
}

defineExpose({ validate: validateAll });
</script>
