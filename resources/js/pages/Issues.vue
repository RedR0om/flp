<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StatusPill from '@/components/monitoring/StatusPill.vue';
import { formatRate, levelColors, type SchoolRow } from '@/types/monitoring';

defineProps<{ schools: SchoolRow[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Issues & Risks', href: '/issues' }] },
});
</script>

<template>
    <Head title="Issues & Risks" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#0a5c9e]">Issues & Risks</h1>
            <p class="text-sm text-[#5a6f86]">Schools below 90% reach, or schools marked as needing technical assistance.</p>
        </div>

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]" data-test="issues">
            <table class="w-full min-w-[760px] text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Cluster</th>
                        <th class="px-4 py-3">Reach</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">4H level</th>
                        <th class="px-4 py-3">TA needed</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="schools.length === 0">
                        <td colspan="6" class="px-4 py-6 text-center text-[#5a6f86]">No schools in view are below 90% reach or marked for technical assistance.</td>
                    </tr>
                    <tr v-for="school in schools" :key="school.id" class="border-t border-[#e6e0d3]" data-test="issue-row">
                        <td class="px-4 py-2.5 font-medium">{{ school.name }}</td>
                        <td class="px-4 py-2.5">{{ school.cluster_label }}</td>
                        <td class="px-4 py-2.5 tabular-nums">{{ formatRate(school.reach_rate) }}</td>
                        <td class="px-4 py-2.5"><StatusPill :status="school.status" :label="school.status_label" /></td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-full px-2 py-0.5 text-xs font-extrabold text-white" :style="{ background: levelColors[school.flp_level] }">
                                {{ school.flp_label }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">{{ school.ta_needed ? 'Yes' : 'No' }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
