<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StatusPill from '@/components/monitoring/StatusPill.vue';
import { formatNumber, formatRate, type SchoolRow } from '@/types/monitoring';

defineProps<{
    totals: { schools: number; learners: number; reached: number; rate: number };
    schools: SchoolRow[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Learner Reach', href: '/learner-reach' }] },
});
</script>

<template>
    <Head title="Learner Reach" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#0a5c9e]">Learner Reach</h1>
            <p class="text-sm text-[#5a6f86]">Reach totals are the sum of school reports that have both a learner count and a reached count.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-3">
                <span class="text-sm text-[#5a6f86]">Schools</span>
                <b class="mt-1 block text-3xl tabular-nums" data-test="reach-schools">{{ formatNumber(totals.schools) }}</b>
            </article>
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-3">
                <span class="text-sm text-[#5a6f86]">Learners</span>
                <b class="mt-1 block text-3xl tabular-nums" data-test="reach-learners">{{ formatNumber(totals.learners) }}</b>
            </article>
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-3">
                <span class="text-sm text-[#5a6f86]">Reached</span>
                <b class="mt-1 block text-3xl tabular-nums">{{ formatNumber(totals.reached) }}</b>
            </article>
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-3">
                <span class="text-sm text-[#5a6f86]">Reach rate</span>
                <b class="mt-1 block text-3xl tabular-nums">{{ formatRate(totals.rate) }}</b>
            </article>
        </div>

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]">
            <table class="w-full min-w-[760px] text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Cluster</th>
                        <th class="px-4 py-3 text-right">Learners</th>
                        <th class="px-4 py-3 text-right">Reached</th>
                        <th class="px-4 py-3 text-right">Rate</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="school in schools" :key="school.id" class="border-t border-[#e6e0d3]" data-test="reach-row">
                        <td class="px-4 py-2.5 font-medium">{{ school.name }}</td>
                        <td class="px-4 py-2.5">{{ school.cluster_label }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ formatNumber(school.learners_total) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ formatNumber(school.learners_reached) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ formatRate(school.reach_rate) }}</td>
                        <td class="px-4 py-2.5"><StatusPill :status="school.status" :label="school.status_label" /></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
