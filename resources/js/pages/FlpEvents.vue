<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatNumber, formatRate, levelColors } from '@/types/monitoring';

type EventRow = {
    id: number;
    school: string;
    cluster: string;
    scenario: string;
    status: string;
    level: string;
    period: string | null;
    learners_total: number | null;
    learners_reached: number | null;
    reach_rate: number | null;
    reach_label: string;
};

const props = defineProps<{ events: EventRow[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'FLP Events', href: '/events' }] },
});

const search = ref('');

const rows = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (query === '') {
        return props.events;
    }

    return props.events.filter((event) => `${event.school} ${event.scenario} ${event.status}`.toLowerCase().includes(query));
});
</script>

<template>
    <Head title="FLP Events" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#0a5c9e]">FLP Events</h1>
            <p class="text-sm text-[#5a6f86]">Each row is a school report currently stored, with the hazard condition linked to that report.</p>
        </div>

        <input
            v-model="search"
            type="search"
            placeholder="Search school, condition, or level"
            aria-label="Search events"
            data-test="event-search"
            class="h-11 max-w-md rounded-xl border border-[#e6e0d3] bg-white px-3"
        />

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]" data-test="event-list">
            <h2 class="border-b-2 border-[#0a5c9e] px-4 py-3 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">Activation records</h2>
            <table class="w-full min-w-[760px] text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">Record</th>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Scenario</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="rows.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-[#5a6f86]">No school reports are in view.</td>
                    </tr>
                    <tr v-for="event in rows" :key="event.id" class="border-t border-[#e6e0d3]" data-test="event-row">
                        <td class="px-4 py-2.5 tabular-nums">{{ event.id }}</td>
                        <td class="px-4 py-2.5">
                            <b class="block">{{ event.school }}</b>
                            <span class="text-xs text-[#5a6f86]">{{ event.cluster }}</span>
                        </td>
                        <td class="px-4 py-2.5">{{ event.scenario }}</td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-full px-2 py-0.5 text-xs font-extrabold text-white" :style="{ background: levelColors[event.level] }">
                                {{ event.status }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]">
            <h2 class="border-b-2 border-[#0a5c9e] px-4 py-3 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">Activation details</h2>
            <table class="w-full min-w-[760px] text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Period</th>
                        <th class="px-4 py-3">Learners</th>
                        <th class="px-4 py-3">Reached</th>
                        <th class="px-4 py-3">Reach</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="event in rows" :key="`detail-${event.id}`" class="border-t border-[#e6e0d3]">
                        <td class="px-4 py-2.5">{{ event.school }}</td>
                        <td class="px-4 py-2.5 tabular-nums">{{ event.period ?? '—' }}</td>
                        <td class="px-4 py-2.5 tabular-nums">{{ formatNumber(event.learners_total) }}</td>
                        <td class="px-4 py-2.5 tabular-nums">{{ formatNumber(event.learners_reached) }}</td>
                        <td class="px-4 py-2.5 tabular-nums">{{ formatRate(event.reach_rate) }} · {{ event.reach_label }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
