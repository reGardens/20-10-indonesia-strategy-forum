<script setup>
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <div
        class="min-h-screen flex flex-col items-center justify-center bg-background px-4"
    >
        <!-- App Logo / Name -->
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                CMS Panel
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Content Management System
            </p>
        </div>

        <!-- Page Content -->
        <div class="w-full max-w-md">
            <slot />
        </div>
    </div>
</template>
