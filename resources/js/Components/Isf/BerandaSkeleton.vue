<template>
    <div
        class="isf-mobile-gutter mx-auto w-full max-w-[1400px] px-4 pb-10 pt-2 lg:px-6 lg:pt-3 animate-pulse"
        aria-busy="true"
        aria-label="Memuat beranda"
    >
        <!-- Sorotan Skeleton -->
        <div class="flex items-center gap-x-3 border-b border-neutral-200 py-3">
            <div class="h-4 w-16 bg-neutral-300 rounded"></div>
            <div class="h-4 w-72 bg-neutral-200 rounded max-w-[60%]"></div>
            <div
                class="ml-auto hidden sm:block h-3.5 w-24 bg-neutral-200 rounded"
            ></div>
        </div>

        <!-- Banner Carousel Skeleton (21:9 ratio) -->
        <div
            class="mt-4 relative border border-neutral-300 bg-neutral-200 aspect-video lg:aspect-[21/9] overflow-hidden flex flex-col justify-end p-4 sm:p-6"
        >
            <div
                class="relative bg-white/95 p-4 sm:p-5 md:max-w-lg border border-neutral-200 shadow-sm"
            >
                <div class="h-3 w-20 bg-red-200 rounded mb-2"></div>
                <div class="h-6 w-3/4 bg-neutral-300 rounded mb-3"></div>
                <div class="h-3.5 w-full bg-neutral-200 rounded mb-1.5"></div>
                <div class="h-3.5 w-2/3 bg-neutral-200 rounded"></div>
            </div>
        </div>

        <!-- 3-Column Skeleton Grid -->
        <div
            class="mt-6 grid grid-cols-1 lg:grid-cols-[minmax(0,23fr)_minmax(0,54fr)_minmax(0,23fr)] lg:border lg:border-neutral-300"
        >
            <!-- Col 1 (ISF Insight Slider + Terkini) -->
            <div
                class="p-4 border-b lg:border-b-0 lg:border-r border-neutral-200 space-y-4"
            >
                <!-- Insight Card Skeleton -->
                <div
                    class="border border-neutral-200 bg-neutral-100/70 p-4 rounded space-y-3"
                >
                    <div class="flex items-center gap-2">
                        <div class="h-2.5 w-2.5 rounded-full bg-red-200"></div>
                        <div class="h-3 w-20 bg-neutral-300 rounded"></div>
                    </div>
                    <div class="h-4 w-4/5 bg-neutral-300 rounded"></div>
                    <div class="h-3 w-full bg-neutral-200 rounded"></div>
                    <div
                        class="flex items-center gap-2.5 pt-2 border-t border-neutral-200"
                    >
                        <div
                            class="h-8 w-8 rounded-full bg-neutral-300 shrink-0"
                        ></div>
                        <div class="space-y-1">
                            <div class="h-3 w-24 bg-neutral-300 rounded"></div>
                            <div
                                class="h-2.5 w-32 bg-neutral-200 rounded"
                            ></div>
                        </div>
                    </div>
                    <div class="space-y-2 pt-2 border-t border-neutral-200">
                        <div class="h-4 bg-neutral-300 rounded w-4/5"></div>
                        <div class="h-4 bg-neutral-200 rounded w-3/5"></div>
                        <div class="h-4 bg-neutral-200 rounded w-2/5"></div>
                    </div>
                </div>

                <div class="h-3.5 w-24 bg-neutral-300 rounded pt-2"></div>
                <div
                    v-for="n in 3"
                    :key="n"
                    class="space-y-1.5 border-b border-neutral-100 pb-3 last:border-b-0"
                >
                    <div class="h-2.5 w-16 bg-neutral-200 rounded"></div>
                    <div class="h-3.5 w-full bg-neutral-300 rounded"></div>
                </div>
            </div>

            <!-- Col 2 (Top News Hero) -->
            <div
                class="p-5 border-b lg:border-b-0 lg:border-r border-neutral-200 space-y-4"
            >
                <div class="h-4 w-20 bg-neutral-300 rounded"></div>
                <div class="h-3 w-24 bg-neutral-200 rounded"></div>
                <div class="h-7 w-4/5 bg-neutral-300 rounded"></div>
                <div class="h-4 w-full bg-neutral-200 rounded"></div>
                <div class="h-4 w-5/6 bg-neutral-200 rounded"></div>
                <div
                    class="h-28 bg-neutral-100 border border-neutral-200 rounded p-4 mt-4 space-y-2"
                >
                    <div class="h-3.5 w-3/4 bg-neutral-200 rounded"></div>
                    <div class="h-3.5 w-1/2 bg-neutral-200 rounded"></div>
                </div>
            </div>

            <!-- Col 3 (Fact block / Chart) -->
            <div class="p-4 space-y-5">
                <div class="h-4 w-28 bg-neutral-300 rounded mb-4"></div>
                <div
                    class="h-24 bg-neutral-100 border border-neutral-200 p-3 rounded space-y-2"
                >
                    <div class="h-3 w-full bg-neutral-200 rounded"></div>
                    <div class="h-3 w-4/5 bg-neutral-200 rounded"></div>
                </div>
                <div class="h-32 bg-neutral-200 rounded"></div>
            </div>
        </div>
    </div>
</template>
