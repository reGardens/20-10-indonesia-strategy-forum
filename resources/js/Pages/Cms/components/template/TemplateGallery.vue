<template>
  <div>
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Pilih Template</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <TemplateCard
        v-for="template in templates"
        :key="template.id"
        :id="template.id"
        :name="template.name"
        :description="template.description"
        :thumbnail="template.thumbnail || ''"
        :selected="template.id === selectedId"
        @select="$emit('select', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import TemplateCard from "./TemplateCard.vue";

defineProps({
  templates: {
    type: Array,
    required: true,
  },
  selectedId: {
    type: String,
    default: "",
  },
});

defineEmits(["select"]);
</script>
