<template>
    <div
        class="space-y-6 animate-pulse"
        aria-busy="true"
        aria-label="Memuat umpan peringatan"
    >
        <header>
            <div class="h-11 border-b border-neutral-300">
                <div class="mt-4 h-3 w-32 rounded bg-neutral-300"></div>
            </div>
            <div class="mt-4 h-10 w-3/4 rounded bg-neutral-300 sm:w-1/2"></div>
        </header>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <section class="space-y-4 lg:col-span-8">
                <div
                    class="grid grid-cols-1 gap-4 border-y border-neutral-300 py-4 sm:grid-cols-[minmax(0,1fr)_14rem]"
                >
                    <div class="space-y-2">
                        <div class="h-3 w-20 rounded bg-neutral-200"></div>
                        <div class="h-10 rounded bg-neutral-200"></div>
                    </div>
                    <div class="space-y-2">
                        <div class="h-3 w-16 rounded bg-neutral-200"></div>
                        <div class="h-10 rounded bg-neutral-200"></div>
                    </div>
                </div>
                <div class="h-4 w-40 rounded bg-neutral-200"></div>
                <div class="overflow-hidden border-y border-neutral-300">
                    <div
                        class="flex h-11 gap-4 border-b border-neutral-300 bg-neutral-100 p-3"
                    >
                        <div class="h-4 w-20 rounded bg-neutral-300"></div>
                        <div class="h-4 flex-1 rounded bg-neutral-300"></div>
                        <div class="h-4 w-20 rounded bg-neutral-300"></div>
                    </div>
                    <div
                        v-for="n in 5"
                        :key="n"
                        class="flex gap-4 border-b border-neutral-200 p-4 last:border-b-0"
                    >
                        <div
                            class="h-5 w-24 shrink-0 rounded bg-neutral-200"
                        ></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-4/5 rounded bg-neutral-300"></div>
                            <div
                                class="h-3 w-full rounded bg-neutral-200"
                            ></div>
                        </div>
                        <div
                            class="h-3 w-20 shrink-0 rounded bg-neutral-200"
                        ></div>
                    </div>
                </div>
            </section>

            <aside class="space-y-6 lg:col-span-4">
                <section class="space-y-4 border border-neutral-300 p-4">
                    <div
                        class="flex items-center gap-4 border-b border-neutral-300 pb-4"
                    >
                        <div class="h-12 w-12 bg-neutral-300"></div>
                        <div class="space-y-2">
                            <div class="h-5 w-32 rounded bg-neutral-300"></div>
                            <div class="h-3 w-24 rounded bg-neutral-200"></div>
                        </div>
                    </div>
                    <div v-for="n in 3" :key="n" class="space-y-2">
                        <div class="h-3 w-20 rounded bg-neutral-200"></div>
                        <div class="h-4 w-full rounded bg-neutral-300"></div>
                    </div>
                    <div class="h-8 w-28 rounded bg-neutral-200"></div>
                </section>
                <section class="space-y-4 border border-neutral-300 p-4">
                    <div class="h-5 w-36 rounded bg-neutral-300"></div>
                    <div
                        v-for="n in 4"
                        :key="n"
                        class="h-4 border-b border-neutral-200 pb-2 rounded bg-neutral-200 last:border-b-0"
                    ></div>
                </section>
            </aside>
        </div>
    </div>
</template>
