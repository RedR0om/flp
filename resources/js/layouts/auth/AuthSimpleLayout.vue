<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh items-center justify-center overflow-hidden bg-[#f6f3ec] p-4 md:p-8">
        <div class="pointer-events-none absolute -top-28 -left-20 size-80 rounded-full bg-[#1fc0da]/25 blur-3xl" />
        <div class="pointer-events-none absolute right-0 -bottom-24 size-96 rounded-full bg-[#0a5c9e]/12 blur-3xl" />

        <div
            class="relative grid w-full max-w-5xl overflow-hidden rounded-[28px] border border-[#e6e0d3] bg-[#fffdf8] shadow-[0_30px_70px_rgba(18,48,79,0.12)] lg:grid-cols-[1.05fr_0.95fr]"
        >
            <aside class="relative hidden flex-col justify-between gap-10 overflow-hidden bg-[#12304f] p-10 text-white lg:flex">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_0%,rgba(31,192,218,0.55),transparent_46%)]" />
                <div class="pointer-events-none absolute -right-16 bottom-10 size-56 rounded-full bg-[#f2663f]/25 blur-3xl" />

                <p class="relative text-xs font-bold tracking-[0.22em] text-[#9ee7f4] uppercase">DepEd · Taguig City and Pateros</p>

                <img
                    src="/images/flp-logo.png"
                    alt="Flexible Learning Program — SDO Taguig City and Pateros"
                    class="relative mx-auto w-60 rounded-3xl bg-white p-2 shadow-[0_18px_30px_rgba(0,0,0,0.28)]"
                />

                <div class="relative">
                    <h2 class="text-3xl leading-tight font-extrabold">Learning continuity, one school at a time.</h2>
                    <p class="mt-3 max-w-sm text-sm leading-6 text-white/75">
                        Reach, 4H levels, and technical assistance for the Schools Division of Taguig City and Pateros.
                    </p>
                    <div class="mt-6 inline-flex items-center rounded-2xl bg-white px-3 py-2">
                        <img src="/images/deped-logo.png" alt="Department of Education" class="h-12 w-auto" />
                    </div>
                </div>
            </aside>

            <div class="flex flex-col justify-center px-6 py-8 sm:px-10 sm:py-12">
                <div class="mb-8 flex flex-col items-center text-center lg:items-start lg:text-left">
                    <img
                        src="/images/flp-logo.png"
                        alt="Flexible Learning Program — SDO Taguig City and Pateros"
                        class="mb-4 h-28 w-auto rounded-2xl bg-white p-1 shadow-md lg:hidden"
                    />
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#0a5c9e]">{{ title }}</h1>
                    <p v-if="description" class="mt-2 max-w-sm text-sm leading-6 text-[#5a6f86]">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
