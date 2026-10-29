<script setup>
defineProps({
    variant: {
        type: String,
        required: true,
        validator: (value) => ["isu", "tokoh"].includes(value),
    },
});
</script>

<template>
    <div
        class="animate-pulse"
        aria-busy="true"
        :aria-label="
            variant === 'isu' ? 'Memuat anatomi isu' : 'Memuat profil tokoh'
        "
    >
        <div
            v-if="variant === 'isu'"
            class="grid grid-cols-1 gap-8 lg:grid-cols-12"
        >
            <div class="space-y-10 lg:col-span-8">
                <article class="space-y-6">
                    <div class="border-b border-neutral-300 pb-6">
                        <div class="h-11 border-b border-neutral-300">
                            <div
                                class="mt-4 h-3 w-36 rounded bg-neutral-300"
                            ></div>
                        </div>
                        <div class="mt-5 h-3 w-28 rounded bg-neutral-200"></div>
                        <div
                            class="mt-3 h-10 w-4/5 rounded bg-neutral-300"
                        ></div>
                        <div
                            class="mt-4 h-4 w-full rounded bg-neutral-200"
                        ></div>
                        <div
                            class="mt-2 h-4 w-5/6 rounded bg-neutral-200"
                        ></div>
                        <div class="mt-4 h-3 w-36 rounded bg-neutral-200"></div>
                    </div>
                    <section
                        v-for="n in 7"
                        :key="n"
                        class="space-y-4 border-t border-neutral-300 py-7"
                    >
                        <div class="h-6 w-52 rounded bg-neutral-300"></div>
                        <template v-if="n === 2"
                            ><div
                                class="aspect-[16/9] border border-neutral-300 bg-neutral-200"
                            ></div
                        ></template>
                        <template v-else-if="n === 4"
                            ><div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div
                                    v-for="actor in 3"
                                    :key="actor"
                                    class="h-28 border border-neutral-300 bg-neutral-100"
                                ></div></div
                        ></template>
                        <template v-else-if="n === 5"
                            ><div
                                class="h-36 border border-neutral-300 bg-neutral-100"
                            ></div
                        ></template>
                        <template v-else
                            ><div
                                class="h-4 w-full rounded bg-neutral-200"
                            ></div>
                            <div
                                class="h-4 w-11/12 rounded bg-neutral-200"
                            ></div>
                            <div class="h-4 w-4/5 rounded bg-neutral-200"></div
                        ></template>
                    </section>
                </article>
                <section
                    v-for="n in 2"
                    :key="`bottom-${n}`"
                    class="space-y-4 border-t border-neutral-300 pt-6"
                >
                    <div class="h-6 w-64 rounded bg-neutral-300"></div>
                    <div
                        v-for="row in 3"
                        :key="row"
                        class="h-5 border-b border-neutral-200 pb-3 rounded bg-neutral-200"
                    ></div>
                </section>
            </div>
            <aside
                class="space-y-7 border-t border-neutral-300 pt-6 lg:col-span-4 lg:border-l lg:pl-7"
            >
                <section class="space-y-4">
                    <div class="h-6 w-32 rounded bg-neutral-300"></div>
                    <div v-for="n in 4" :key="n" class="flex gap-3">
                        <div class="h-7 w-5 rounded bg-neutral-200"></div>
                        <div class="h-5 flex-1 rounded bg-neutral-300"></div>
                    </div>
                </section>
                <section
                    class="space-y-3 border border-neutral-300 bg-neutral-100 p-5"
                >
                    <div class="h-6 w-40 rounded bg-neutral-300"></div>
                    <div class="h-4 w-full rounded bg-neutral-200"></div>
                    <div class="h-4 w-5/6 rounded bg-neutral-200"></div>
                    <div class="h-4 w-4/5 rounded bg-neutral-200"></div>
                </section>
            </aside>
        </div>

        <div v-else class="space-y-10">
            <header>
                <div class="h-11 border-b border-neutral-300">
                    <div class="mt-4 h-3 w-28 rounded bg-neutral-300"></div>
                </div>
                <div class="mt-4 h-10 w-3/5 rounded bg-neutral-300"></div>
            </header>
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <div class="space-y-10 lg:col-span-8">
                    <section class="border border-neutral-300 p-6">
                        <div
                            class="grid grid-cols-1 gap-6 sm:grid-cols-[10rem_minmax(0,1fr)]"
                        >
                            <div class="aspect-[3/4] bg-neutral-200"></div>
                            <div class="space-y-5">
                                <div
                                    class="h-8 w-3/4 rounded bg-neutral-300"
                                ></div>
                                <div
                                    class="h-4 w-full rounded bg-neutral-200"
                                ></div>
                                <div
                                    class="grid grid-cols-2 gap-4 md:grid-cols-4"
                                >
                                    <div
                                        v-for="n in 4"
                                        :key="n"
                                        class="h-16 border-y border-neutral-300 bg-neutral-100"
                                    ></div>
                                </div>
                                <div
                                    class="space-y-3 border-y border-neutral-300 py-4"
                                >
                                    <div
                                        class="h-4 w-32 rounded bg-neutral-300"
                                    ></div>
                                    <div
                                        class="h-2 w-full bg-neutral-200"
                                    ></div>
                                    <div
                                        class="h-3 w-4/5 rounded bg-neutral-200"
                                    ></div>
                                </div>
                                <div
                                    class="h-9 w-32 rounded bg-neutral-300"
                                ></div>
                            </div>
                        </div>
                    </section>
                    <section class="space-y-4 border-t border-neutral-300 pt-5">
                        <div class="h-6 w-64 rounded bg-neutral-300"></div>
                        <div class="overflow-hidden border border-neutral-300">
                            <div
                                class="h-10 border-b border-neutral-300 bg-neutral-100"
                            ></div>
                            <div
                                v-for="n in 4"
                                :key="n"
                                class="h-12 border-b border-neutral-200 last:border-b-0"
                            ></div>
                        </div>
                        <div class="h-3 w-2/5 rounded bg-neutral-200"></div>
                    </section>
                </div>
                <aside
                    class="space-y-7 border-t border-neutral-300 pt-6 lg:col-span-4 lg:border-l lg:pl-7"
                >
                    <section class="space-y-4">
                        <div class="h-6 w-36 rounded bg-neutral-300"></div>
                        <div
                            v-for="n in 3"
                            :key="n"
                            class="h-5 border-b border-neutral-200 pb-3 rounded bg-neutral-200"
                        ></div>
                    </section>
                    <section class="space-y-4 border-t border-neutral-300 pt-5">
                        <div class="h-6 w-36 rounded bg-neutral-300"></div>
                        <div
                            class="h-64 border border-neutral-300 bg-neutral-100"
                        ></div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</template>
