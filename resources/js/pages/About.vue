<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{ schools: number; clusters: number }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'About', href: '/about' }] },
});

const tabs = [
    ['Overview', 'Division totals, the Taguig school map, status mix, school snapshot, and the reach-rate trend saved from real reports.'],
    ['Section Details', 'Sections and learner internet access for the schools you can edit.'],
    ['Modality Monitoring', 'Home-Based, Blended, Online, Printed ADM, and Offline Digital / HTML counts, summed from each school.'],
    ['School Status', 'Learners, reach, 4H level, and whether technical assistance is needed.'],
    ['Validation / Home Visitation', 'Findings marked compliant, minor concern, or significant concern.'],
    ['Action / Technical Assistance', 'Concerns, the assigned action, who is responsible, and status.'],
    ['Reports & Export', 'School-status and action CSV files, plus a one-page narrative you can print or download.'],
    ['4H Hazard Mapping', 'The division condition and the HAYO, HINAY, HINGA, or HINTO level it maps to.'],
];
</script>

<template>
    <Head title="About" />

    <div class="flex max-w-3xl flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Flexible Learning Program</h1>
            <p class="mt-2 text-sm leading-6 text-[#5a6f86]">
                This monitors learning continuity for the Schools Division of Taguig City and Pateros. Schools report on the 4H scale —
                HAYO (continue), HINAY (ease in), HINGA (check in), and HINTO (stop) — and the division follows the 5R cycle: Report, Review,
                Reach and Validate, Respond, and Resolve.
            </p>
        </div>

        <section class="grid gap-3 sm:grid-cols-2">
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-4">
                <span class="text-xs text-[#5a6f86]">Schools</span>
                <b class="block text-3xl">{{ schools }}</b>
            </article>
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-4">
                <span class="text-xs text-[#5a6f86]">Clusters</span>
                <b class="block text-3xl">{{ clusters }}</b>
            </article>
        </section>

        <section class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
            <h2 class="mb-3 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">What each screen covers</h2>
            <ul class="flex flex-col gap-3">
                <li v-for="[title, detail] in tabs" :key="title">
                    <b class="text-sm">{{ title }}</b>
                    <p class="text-sm text-[#5a6f86]">{{ detail }}</p>
                </li>
            </ul>
        </section>
    </div>
</template>
