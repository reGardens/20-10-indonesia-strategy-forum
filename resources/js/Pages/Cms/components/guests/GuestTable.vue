<template>
  <div class="guest-table overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th
            scope="col"
            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
          >
            Nama
          </th>
          <th
            scope="col"
            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
          >
            No. Telepon
          </th>
          <th
            scope="col"
            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
          >
            Maks. Tamu
          </th>
          <th
            scope="col"
            class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
          >
            Aksi
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-if="guests.length === 0">
          <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">
            Tidak ada data tamu.
          </td>
        </tr>
        <tr
          v-for="guest in guests"
          :key="guest.id"
          class="hover:bg-gray-50 transition-colors"
        >
          <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
            {{ guest.name }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
            {{ guest.phone || "-" }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
            {{ guest.maxPax || 1 }}
          </td>
          <td class="px-4 py-3 text-right whitespace-nowrap">
            <div class="flex items-center justify-end gap-2">
              <button
                type="button"
                class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
                @click="$emit('edit', guest)"
              >
                Edit
              </button>
              <button
                type="button"
                class="rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
                @click="$emit('delete', guest)"
              >
                Hapus
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  guests: {
    type: Array,
    default: () => [],
  },
});

defineEmits(["edit", "delete"]);
</script>
