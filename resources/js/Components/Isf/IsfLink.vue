<script setup>
/**
 * Link for CMS-managed targets (Path di situs atau URL lengkap https://…).
 * - absolute http(s) or protocol-relative (//) URL → <a target="_blank" rel="noopener noreferrer">
 * - in-site path handled by the ISF SPA router → <router-link>
 * - known in-site path (Blade/Inertia pages in ALLOWED_PATHS, e.g. /anggota/briefing/preview) → <a href>
 * - anything else (page not built yet / empty target) → placeholder <a href="#"> that does not navigate
 * Class and other attributes fall through to the rendered element.
 */
import { computed } from "vue";
import { useRouter } from "vue-router";

const props = defineProps({
    to: { type: String, default: "" },
});

const router = useRouter();

// Non-SPA in-site pages that really exist (served by Blade/Inertia, not the SPA router).
const ALLOWED_PATHS = ["/anggota/briefing/preview", "/login"];

// http://, https:// or protocol-relative //host
const isExternal = computed(() => /^(https?:)?\/\//i.test(props.to));

const isSpaRoute = computed(() => {
    if (isExternal.value || !props.to || !props.to.startsWith("/"))
        return false;
    try {
        return router.resolve(props.to).matched.length > 0;
    } catch {
        return false;
    }
});

// A real, reachable in-site Blade/Inertia page (full page load).
const isAllowedPath = computed(() =>
    ALLOWED_PATHS.some((p) => props.to === p || props.to.startsWith(p + "?")),
);

// Target is not yet available (feature/page not built). Render a dead link to "#".
const isPlaceholder = computed(
    () => !isSpaRoute.value && !isExternal.value && !isAllowedPath.value,
);

function onPlaceholderClick(event) {
    // Prevent jumping to top / changing the URL for not-yet-available targets.
    event.preventDefault();
}
</script>

<template>
    <router-link v-if="isSpaRoute" :to="to" class="isf-link"
        ><slot
    /></router-link>
    <a
        v-else-if="isExternal"
        :href="to"
        target="_blank"
        rel="noopener noreferrer"
        class="isf-link"
        ><slot /><span class="sr-only"> (membuka tab baru)</span></a
    >
    <a v-else-if="isAllowedPath" :href="to" class="isf-link"><slot /></a>
    <a
        v-else
        href="#"
        aria-disabled="true"
        title="Halaman belum tersedia"
        class="isf-link"
        @click="onPlaceholderClick"
        ><slot
    /></a>
</template>
