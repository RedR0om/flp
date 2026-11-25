<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DonutChart from '@/components/monitoring/DonutChart.vue';
import TaguigMap from '@/components/monitoring/TaguigMap.vue';
import TrendChart from '@/components/monitoring/TrendChart.vue';
import StatusPill from '@/components/monitoring/StatusPill.vue';
import { dashboard } from '@/routes';
import {
    formatNumber,
    formatRate,
    levelColors,
    type ClusterOption,
    type EnrollmentSummary,
    type FlpLevelOption,
    type MonitoringFilters,
    type SchoolRow,
    type StatusSegment,
} from '@/types/monitoring';

type Totals = {
    schools: number;
    learners: number;
    reached: number;
    rate: number;
    issues: number;
    ta: number;
};

type Step = {
    title: string;
    detail: string;
    color: string;
};

type Hazard = {
    condition: string;
    level: string;
    label: string;
} | null;

const props = defineProps<{
    totals: Totals;
    enrollment: EnrollmentSummary;
    segments: StatusSegment[];
    schools: SchoolRow[];
    snapshot: SchoolRow[];
    priorities: SchoolRow[];
    hazard: Hazard;
    levels: FlpLevelOption[];
    clusters: ClusterOption[];
    filters: MonitoringFilters;
    trend: Array<{ date: string; rate: number | null }>;
    meta: {
        updated_at: string | null;
        submissions_today: number;
    };
    steps: Step[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Overview',
                href: dashboard(),
            },
        ],
    },
});

const search = ref(props.filters.q);
const announcementOpen = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

const selectedCluster = computed(() =>
    props.clusters.find((cluster) => String(cluster.number) === props.filters.cluster),
);

const tiles = computed(() => [
    { label: 'Total schools', value: formatNumber(props.totals.schools), test: 'kpi-schools' },
    { label: 'Total learners', value: formatNumber(props.totals.learners), test: 'kpi-learners' },
    { label: 'Learners reached', value: formatNumber(props.totals.reached), test: 'kpi-reached' },
    { label: 'Reach rate', value: formatRate(props.totals.rate), test: 'kpi-rate' },
    { label: 'Schools with issues', value: formatNumber(props.totals.issues), test: 'kpi-issues' },
    { label: 'Need immediate TA', value: formatNumber(props.totals.ta), test: 'kpi-ta' },
]);

function visit(query: { cluster?: string; q?: string }) {
    router.get(
        dashboard(),
        {
            cluster: query.cluster && query.cluster !== 'all' ? query.cluster : undefined,
            q: query.q || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function setCluster(value: string) {
    visit({ cluster: value, q: search.value });
}

watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => visit({ cluster: props.filters.cluster, q: value }), 250);
});
</script>

<template>
    <Head title="Overview" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#0a5c9e] md:text-3xl">
                FLP Implementation Monitoring Dashboard
            </h1>
            <p class="text-sm text-[#5a6f86]">
                Flexible Learning Program (FLP) · Real-time monitoring for learning continuity
            </p>
        </div>

        <div class="flex flex-wrap items-stretch gap-3">
            <div class="rounded-xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-2">
                <span class="block text-xs text-[#5a6f86]">Last data update</span>
                <b>{{ meta.updated_at ?? 'No reports yet' }}</b>
            </div>
            <div class="rounded-xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-2">
                <span class="block text-xs text-[#5a6f86]">Reports updated today</span>
                <b data-test="submissions-today">{{ meta.submissions_today }}</b>
            </div>
            <label v-if="clusters.length" class="rounded-xl border border-[#e6e0d3] bg-[#fffdf8] px-3 py-2 text-sm font-semibold">
                <span class="sr-only">Filter by cluster</span>
                <select
                    class="bg-transparent outline-none"
                    :value="filters.cluster"
                    data-test="cluster-filter"
                    @change="setCluster(($event.target as HTMLSelectElement).value)"
                >
                    <option value="all">All clusters</option>
                    <option v-for="cluster in clusters" :key="cluster.id" :value="String(cluster.number)">
                        Cluster {{ cluster.number }}
                    </option>
                </select>
            </label>
            <div v-if="selectedCluster" class="rounded-xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-2">
                <span class="block text-xs text-[#5a6f86]">
                    Cluster {{ selectedCluster.number }} supervisor ({{ selectedCluster.supervisor_title }})
                </span>
                <b>{{ selectedCluster.supervisor_name }}</b>
            </div>
            <div v-if="hazard" class="rounded-xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-2">
                <span class="block text-xs text-[#5a6f86]">Active condition</span>
                <b>{{ hazard.condition }}</b>
                <span
                    class="ml-2 inline-flex rounded-lg px-2 py-0.5 text-xs font-extrabold text-white"
                    :style="{ background: levelColors[hazard.level] }"
                >
                    {{ hazard.label }}
                </span>
            </div>
        </div>

        <ol class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <li
                v-for="(step, index) in steps"
                :key="step.title"
                class="flex gap-3 rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-3"
            >
                <span
                    class="grid size-7 shrink-0 place-items-center rounded-full text-sm font-extrabold text-white"
                    :style="{ background: step.color }"
                >
                    {{ index + 1 }}
                </span>
                <span>
                    <b class="block text-sm">{{ step.title }}</b>
                    <span class="text-xs text-[#5a6f86]">{{ step.detail }}</span>
                </span>
            </li>
        </ol>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="tile in tiles"
                :key="tile.label"
                class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-3"
            >
                <span class="text-sm font-semibold text-[#5a6f86]">{{ tile.label }}</span>
                <b class="mt-1 block text-3xl tracking-tight tabular-nums" :data-test="tile.test">{{ tile.value }}</b>
            </article>
        </div>

        <section class="grid gap-4 rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5 lg:grid-cols-[minmax(0,1fr)_270px]">
            <h2 class="border-b-2 border-[#0a5c9e] pb-2 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase lg:col-span-2">
                School mapping
            </h2>
            <div class="mx-auto w-full max-w-[480px]">
                <TaguigMap :schools="schools" />
                <p class="mt-2 text-xs leading-snug text-[#5a6f86]">
                    Each barangay has its own border. Area color follows the nearest school’s current 4H level. Pin color follows
                    that school’s reach status. Pin locations are approximate.
                </p>
            </div>
            <div class="flex flex-col gap-4">
                <div>
                    <h3 class="mb-2 text-sm font-extrabold">Learning continuity level</h3>
                    <ul class="flex flex-col gap-3">
                        <li v-for="level in levels" :key="level.value" class="flex gap-2 text-sm">
                            <span class="mt-1 size-3.5 shrink-0 rounded-full" :style="{ background: levelColors[level.value] }" />
                            <span>
                                <b class="block text-[12.5px]">{{ level.label }}</b>
                                <span class="block text-xs leading-snug text-[#5a6f86]">{{ level.description }}</span>
                            </span>
                        </li>
                    </ul>
                </div>
                <button
                    type="button"
                    class="overflow-hidden rounded-xl border border-[#a9e6f1] bg-[#e7f8fb] text-left"
                    aria-label="Announcement – click to enlarge"
                    @click="announcementOpen = true"
                >
                    <span class="grid h-44 place-items-center bg-gradient-to-br from-[#1fc0da] to-[#00b3dc] text-[#0a2a4a]">
                        <span class="text-center">
                            <b class="block text-xl tracking-widest">ANNOUNCEMENT</b>
                            <span class="text-sm">Picture placeholder</span>
                        </span>
                    </span>
                    <span class="flex justify-between gap-2 px-3 py-2 text-xs font-bold text-[#0a6a80]">
                        <span>Announcement · Aug 17, 2026</span>
                        <span class="underline">Click to enlarge</span>
                    </span>
                </button>
            </div>
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
                <h2 class="mb-4 border-b-2 border-[#0a5c9e] pb-2 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">
                    Implementation by status
                </h2>
                <DonutChart :segments="segments" :total="totals.schools" />
            </section>
            <section class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
                <h2 class="mb-4 border-b-2 border-[#0a5c9e] pb-2 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">
                    Reach rate trend
                </h2>
                <TrendChart :points="trend" />
            </section>
        </div>

        <section class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5" data-test="enrollment-card">
            <h2 class="mb-4 border-b-2 border-[#0a5c9e] pb-2 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">
                Enrollment
            </h2>
            <div class="mb-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-4 py-3">
                    <span class="text-sm text-[#5a6f86]">Total enrollment</span>
                    <b class="mt-1 block text-2xl tabular-nums" data-test="enrollment-total">{{ formatNumber(enrollment.total) }}</b>
                </div>
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-4 py-3">
                    <span class="text-sm text-[#5a6f86]">Schools with enrollment records</span>
                    <b class="mt-1 block text-2xl tabular-nums" data-test="enrollment-records">
                        {{ formatNumber(enrollment.with_records) }} of {{ formatNumber(enrollment.schools) }}
                    </b>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] text-sm">
                    <thead class="text-left text-xs text-[#5a6f86]">
                        <tr>
                            <th class="px-3 py-2">Cluster</th>
                            <th class="px-3 py-2 text-right">Schools</th>
                            <th class="px-3 py-2 text-right">Enrollment</th>
                            <th class="px-3 py-2">Share of enrollment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in enrollment.rows" :key="row.cluster" class="border-t border-[#e6e0d3]" data-test="enrollment-row">
                            <td class="px-3 py-2.5 font-medium">{{ row.label }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ formatNumber(row.schools) }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">
                                {{ row.with_enrollment ? formatNumber(row.enrollment) : '—' }}
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="flex items-center gap-2">
                                    <span class="h-2 w-28 overflow-hidden rounded-full bg-[#efeae0]">
                                        <span class="block h-full bg-[#0a5c9e]" :style="{ width: `${row.share}%` }" />
                                    </span>
                                    <span class="tabular-nums">{{ row.share.toFixed(1) }}%</span>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs leading-snug text-[#5a6f86]">
                Each cluster total is the sum of submitted learner counts. Schools with no count are left out. Grade level and sex
                breakdowns are not in the current data.
            </p>
        </section>

        <section class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
            <h2 class="mb-4 border-b-2 border-[#0a5c9e] pb-2 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">
                School status snapshot
            </h2>
            <input
                v-model="search"
                type="search"
                placeholder="Search school…"
                aria-label="Search school"
                data-test="overview-search"
                class="mb-3 h-11 w-full rounded-xl border border-[#e6e0d3] bg-white px-3"
            />
            <div class="max-h-[520px] overflow-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-[#fffdf8] text-left text-xs text-[#5a6f86]">
                        <tr>
                            <th class="px-3 py-2">School</th>
                            <th class="px-3 py-2">Reach rate</th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="snapshot.length === 0">
                            <td colspan="3" class="px-3 py-6 text-center text-[#5a6f86]">No schools match your search.</td>
                        </tr>
                        <tr v-for="school in snapshot" :key="school.id" class="border-t border-[#e6e0d3]" data-test="snapshot-row">
                            <td class="px-3 py-2.5">{{ school.name }}</td>
                            <td class="px-3 py-2.5 tabular-nums">{{ formatRate(school.reach_rate) }}</td>
                            <td class="px-3 py-2.5">
                                <StatusPill :status="school.status" :label="school.status_label" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
            <h2 class="mb-4 border-b-2 border-[#0a5c9e] pb-2 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">
                Top priority for follow-up
            </h2>
            <p v-if="priorities.length === 0" class="text-sm text-[#5a6f86]">
                No schools are flagged for technical assistance.
            </p>
            <ol v-else class="grid gap-3">
                <li v-for="(school, index) in priorities" :key="school.id" class="flex items-center gap-3">
                    <span class="grid size-8 place-items-center rounded-full bg-[#fde4e2] font-extrabold text-[#bf2a23]">
                        {{ index + 1 }}
                    </span>
                    <span>
                        <b class="block">{{ school.name }}</b>
                        <span class="text-sm text-[#5a6f86]">
                            Reach rate {{ formatRate(school.reach_rate) }} – TA requested
                        </span>
                    </span>
                </li>
            </ol>
        </section>
    </div>

    <div
        v-if="announcementOpen"
        class="fixed inset-0 z-50 grid place-items-center bg-[#061424]/60 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Announcement"
        @click.self="announcementOpen = false"
    >
        <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-[#fffdf8]">
            <div class="flex items-center justify-between border-b border-[#e6e0d3] px-4 py-3">
                <b>Announcement</b>
                <button type="button" class="rounded-lg border border-[#e6e0d3] px-3 py-1 text-sm" @click="announcementOpen = false">
                    Close
                </button>
            </div>
            <div class="grid h-72 place-items-center bg-gradient-to-br from-[#1fc0da] to-[#00b3dc] text-[#0a2a4a]">
                <span class="text-center">
                    <b class="block text-3xl tracking-widest">ANNOUNCEMENT</b>
                    <span>Picture placeholder</span>
                </span>
            </div>
        </div>
    </div>
</template>
