<template>
    <div
        class="isf-mobile-gutter mx-auto w-full max-w-[1400px] px-4 py-8 sm:py-10 lg:px-6 animate-pulse"
        aria-busy="true"
        aria-label="Memuat koleksi infografik"
    >
        <div class="mb-6 flex items-center justify-between border-b border-neutral-300 pb-3">
            <div class="h-3 w-32 rounded bg-neutral-200"></div>
            <div class="h-3 w-32 rounded bg-neutral-200"></div>
        </div>
        <header class="mb-8 max-w-3xl">
            <div class="h-3 w-44 rounded bg-neutral-300"></div>
            <div class="mt-3 h-10 w-4/5 rounded bg-neutral-300"></div>
            <div class="mt-3 h-4 w-full rounded bg-neutral-200"></div>
            <div class="mt-2 h-4 w-5/6 rounded bg-neutral-200"></div>
            <div class="mt-6 flex flex-wrap gap-2"><div v-for="n in 4" :key="n" class="h-8 w-24 rounded-full bg-neutral-200"></div></div>
        </header>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8 xl:grid-cols-4">
            <article v-for="n in 8" :key="n" class="overflow-hidden rounded-xs border border-neutral-300 bg-white shadow-2xs"><div class="aspect-[4/3] bg-neutral-200"></div><div class="space-y-3 p-4 sm:p-5"><div class="h-3 w-20 rounded bg-neutral-200"></div><div class="h-5 w-full rounded bg-neutral-300"></div><div class="h-5 w-4/5 rounded bg-neutral-300"></div><div class="h-3 w-1/3 border-t border-neutral-200 pt-3 rounded bg-neutral-200"></div></div></article>
        </div>
    </div>
</template>
