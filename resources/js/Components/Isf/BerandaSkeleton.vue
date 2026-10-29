<template>
    <div
        class="isf-mobile-gutter mx-auto w-full max-w-[1400px] px-4 pb-10 pt-2 lg:px-6 lg:pt-3 animate-pulse"
        aria-busy="true"
        aria-label="Memuat beranda"
    >
        <div
            class="flex items-center gap-x-3 border-b border-neutral-300 py-2.5"
        >
            <div class="h-3 w-16 rounded bg-neutral-300"></div>
            <div class="h-3 w-2/5 rounded bg-neutral-200"></div>
        </div>

        <div
            class="mt-4 overflow-hidden border border-neutral-300 bg-neutral-200"
        >
            <div class="aspect-video lg:aspect-[21/9]"></div>
            <div
                class="flex items-center justify-between border-t border-neutral-300 bg-white px-4 py-3"
            >
                <div class="h-3 w-32 rounded bg-neutral-200"></div>
                <div class="flex gap-2">
                    <div class="h-7 w-7 rounded-full bg-neutral-200"></div>
                    <div class="h-7 w-7 rounded-full bg-neutral-200"></div>
                </div>
            </div>
        </div>

        <div class="mt-6 lg:border lg:border-neutral-300">
            <div
                class="grid grid-cols-1 lg:grid-cols-[minmax(0,23fr)_minmax(0,54fr)_minmax(0,23fr)]"
            >
                <section
                    class="order-1 min-w-0 lg:order-2 lg:border-x lg:border-neutral-300"
                >
                    <div
                        class="h-11 border-b border-neutral-300 px-4 xl:px-[30px]"
                    >
                        <div class="mt-4 h-3 w-20 rounded bg-neutral-300"></div>
                    </div>
                    <div class="space-y-4 px-4 pb-6 pt-5 xl:px-[30px]">
                        <div class="h-3 w-28 rounded bg-neutral-200"></div>
                        <div class="h-10 w-4/5 rounded bg-neutral-300"></div>
                        <div class="h-4 w-full rounded bg-neutral-200"></div>
                        <div class="h-4 w-5/6 rounded bg-neutral-200"></div>
                        <div
                            class="aspect-[16/9] border border-neutral-300 bg-neutral-200"
                        ></div>
                        <div
                            class="grid grid-cols-1 border-t border-neutral-300 sm:grid-cols-2"
                        >
                            <div
                                v-for="n in 2"
                                :key="n"
                                class="space-y-2 border-b border-neutral-300 py-4 sm:px-4 sm:first:px-0 sm:last:border-l sm:last:border-neutral-300"
                            >
                                <div
                                    class="h-3 w-full rounded bg-neutral-200"
                                ></div>
                                <div
                                    class="h-3 w-3/4 rounded bg-neutral-200"
                                ></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="order-2 min-w-0 border-t border-neutral-300 lg:order-3 lg:border-t-0"
                >
                    <div
                        class="hidden h-11 border-b border-neutral-300 lg:block"
                    ></div>
                    <div class="px-4 xl:px-[30px]">
                        <div
                            v-for="n in 3"
                            :key="n"
                            class="space-y-3 border-b border-neutral-300 py-4 lg:py-5"
                        >
                            <div
                                class="aspect-video border border-neutral-300 bg-neutral-200"
                            ></div>
                            <div
                                class="h-4 w-full rounded bg-neutral-300"
                            ></div>
                            <div class="h-3 w-1/3 rounded bg-neutral-200"></div>
                        </div>
                    </div>
                </section>

                <aside
                    class="order-3 min-w-0 border-t border-neutral-300 lg:order-1 lg:border-t-0"
                >
                    <div class="space-y-3 p-4 xl:px-[30px]">
                        <div class="h-3 w-24 rounded bg-neutral-300"></div>
                        <div class="h-5 w-4/5 rounded bg-neutral-300"></div>
                        <div class="h-3 w-full rounded bg-neutral-200"></div>
                        <div
                            class="h-20 border border-neutral-300 bg-neutral-100"
                        ></div>
                    </div>
                    <div class="border-t border-neutral-300">
                        <div
                            class="h-11 border-b border-neutral-300 px-4 xl:px-[30px]"
                        >
                            <div
                                class="mt-4 h-3 w-24 rounded bg-neutral-300"
                            ></div>
                        </div>
                        <div class="px-4 xl:px-[30px]">
                            <div
                                v-for="n in 3"
                                :key="n"
                                class="flex gap-3 border-b border-neutral-300 py-3"
                            >
                                <div
                                    class="h-14 w-14 shrink-0 bg-neutral-200"
                                ></div>
                                <div class="flex-1 space-y-2">
                                    <div
                                        class="h-3 w-16 rounded bg-neutral-200"
                                    ></div>
                                    <div
                                        class="h-4 w-full rounded bg-neutral-300"
                                    ></div>
                                    <div
                                        class="h-3 w-1/3 rounded bg-neutral-200"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <section class="border-t border-neutral-300">
                <div
                    class="flex h-11 items-center justify-between border-b border-neutral-300 px-4 xl:px-[30px]"
                >
                    <div class="h-3 w-32 rounded bg-neutral-300"></div>
                    <div class="h-3 w-16 rounded bg-neutral-200"></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="n in 4"
                        :key="n"
                        class="space-y-3 border-b border-neutral-300 px-4 py-4 sm:px-3 lg:border-b-0 lg:border-r lg:px-4 lg:py-5 last:border-r-0 xl:px-[30px]"
                    >
                        <div class="aspect-[4/3] bg-neutral-200"></div>
                        <div class="h-3 w-16 rounded bg-neutral-200"></div>
                        <div class="h-5 w-full rounded bg-neutral-300"></div>
                        <div class="h-3 w-1/3 rounded bg-neutral-200"></div>
                    </div>
                </div>
            </section>

            <section class="border-t border-neutral-300">
                <div class="h-11 border-b border-neutral-300 px-4 xl:px-[30px]">
                    <div class="mt-4 h-3 w-20 rounded bg-neutral-300"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2">
                    <div
                        v-for="n in 2"
                        :key="n"
                        class="space-y-3 border-b border-neutral-300 px-4 py-5 last:border-b-0 md:border-b-0 md:px-4 md:first:border-r xl:px-[30px]"
                    >
                        <div class="h-3 w-16 rounded bg-neutral-200"></div>
                        <div class="h-6 w-4/5 rounded bg-neutral-300"></div>
                        <div class="h-4 w-full rounded bg-neutral-200"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
