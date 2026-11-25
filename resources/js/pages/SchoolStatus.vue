<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import StatusPill from '@/components/monitoring/StatusPill.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import {
    formatNumber,
    formatRate,
    levelColors,
    statusActions,
    type ClusterOption,
    type FlpLevelOption,
    type MonitoringFilters,
    type SchoolRow,
} from '@/types/monitoring';

const props = defineProps<{
    schools: SchoolRow[];
    levels: FlpLevelOption[];
    clusters: ClusterOption[];
    filters: MonitoringFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'School Status',
                href: '/schools',
            },
        ],
    },
});

const page = usePage<{
    can: { manageSchools: boolean; filterClusters: boolean };
}>();

const search = ref(props.filters.q);
const showAdd = ref(false);
const selected = ref<SchoolRow | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;

const form = useForm({
    name: '',
    cluster_id: '',
    head_name: '',
});

function visit(query: { cluster?: string; q?: string }) {
    router.get(
        '/schools',
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

function save(school: SchoolRow, patch: Partial<Pick<SchoolRow, 'learners_total' | 'learners_reached' | 'flp_level' | 'ta_needed'>>) {
    router.patch(
        `/schools/${school.id}/report`,
        {
            learners_total: school.learners_total,
            learners_reached: school.learners_reached,
            flp_level: school.flp_level,
            ta_needed: school.ta_needed,
            ...patch,
        },
        { preserveScroll: true },
    );
}

function onNumber(
    school: SchoolRow,
    field: 'learners_total' | 'learners_reached',
    event: Event,
) {
    const value = (event.target as HTMLInputElement).value;
    save(school, { [field]: value === '' ? null : Number(value) });
}

function remove(school: SchoolRow) {
    if (!confirm(`Remove ${school.name}?`)) {
        return;
    }

    router.delete(`/schools/${school.id}`, { preserveScroll: true });
}

function openSchool(school: SchoolRow) {
    selected.value = school;
}

function addSchool() {
    form.post('/schools', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAdd.value = false;
        },
    });
}
</script>

<template>
    <Head title="School Status" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#0a5c9e]">School status</h1>
            <p class="text-sm text-[#5a6f86]">Reach rate, FLP level, and technical assistance for schools in your scope.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <input
                v-model="search"
                type="search"
                placeholder="Search school…"
                aria-label="Search school"
                data-test="school-search"
                class="h-11 min-w-56 flex-1 rounded-xl border border-[#e6e0d3] bg-white px-3"
            />
            <label v-if="page.props.can.filterClusters" class="rounded-xl border border-[#e6e0d3] bg-[#fffdf8] px-3 py-2 text-sm font-semibold">
                <span class="sr-only">Filter by cluster</span>
                <select
                    class="bg-transparent outline-none"
                    :value="filters.cluster"
                    data-test="school-cluster-filter"
                    @change="setCluster(($event.target as HTMLSelectElement).value)"
                >
                    <option value="all">All clusters</option>
                    <option v-for="cluster in clusters" :key="cluster.id" :value="String(cluster.number)">
                        Cluster {{ cluster.number }}
                    </option>
                </select>
            </label>
            <Button v-if="page.props.can.manageSchools" type="button" data-test="add-school" @click="showAdd = !showAdd">
                Add school
            </Button>
        </div>

        <form
            v-if="showAdd && page.props.can.manageSchools"
            class="grid gap-3 rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-4 md:grid-cols-4"
            @submit.prevent="addSchool"
        >
            <div class="grid gap-1">
                <Label for="school-name">School</Label>
                <Input id="school-name" v-model="form.name" required data-test="new-school-name" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-1">
                <Label for="school-cluster">Cluster</Label>
                <select
                    id="school-cluster"
                    v-model="form.cluster_id"
                    required
                    data-test="new-school-cluster"
                    class="h-9 rounded-md border border-[#e6e0d3] bg-white px-3 text-sm"
                >
                    <option value="" disabled>Select cluster</option>
                    <option v-for="cluster in clusters" :key="cluster.id" :value="cluster.id">
                        Cluster {{ cluster.number }}
                    </option>
                </select>
                <InputError :message="form.errors.cluster_id" />
            </div>
            <div class="grid gap-1">
                <Label for="school-head">School head</Label>
                <Input id="school-head" v-model="form.head_name" />
                <InputError :message="form.errors.head_name" />
            </div>
            <div class="flex items-end">
                <Button type="submit" :disabled="form.processing" data-test="save-school">Save school</Button>
            </div>
        </form>

        <p v-if="page.props.errors.learners_reached" class="text-sm text-[#bf2a23]">
            {{ page.props.errors.learners_reached }}
        </p>

        <div class="overflow-x-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]">
            <table class="w-full min-w-[960px] text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-3 py-3">School</th>
                        <th class="px-3 py-3">Cluster</th>
                        <th class="px-3 py-3">School head</th>
                        <th class="px-3 py-3 text-right">Learners</th>
                        <th class="px-3 py-3 text-right">Learners reached</th>
                        <th class="px-3 py-3 text-right">Reach rate</th>
                        <th class="px-3 py-3">FLP level</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">TA needed</th>
                        <th class="px-3 py-3" />
                        <th v-if="page.props.can.manageSchools" class="px-3 py-3" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="schools.length === 0">
                        <td :colspan="page.props.can.manageSchools ? 11 : 10" class="px-3 py-8 text-center text-[#5a6f86]">
                            No schools match your search.
                        </td>
                    </tr>
                    <tr v-for="school in schools" :key="school.id" class="border-t border-[#e6e0d3]" data-test="school-row">
                        <td class="px-3 py-2.5 font-medium">{{ school.name }}</td>
                        <td class="px-3 py-2.5">{{ school.cluster_label }}</td>
                        <td class="px-3 py-2.5">{{ school.head_name ?? '—' }}</td>
                        <td class="px-3 py-2.5 text-right">
                            <input
                                class="w-24 rounded-lg border border-[#e6e0d3] px-2 py-1 text-right tabular-nums"
                                inputmode="numeric"
                                :value="school.learners_total ?? ''"
                                :aria-label="`Total learners, ${school.name}`"
                                @change="onNumber(school, 'learners_total', $event)"
                            />
                        </td>
                        <td class="px-3 py-2.5 text-right">
                            <input
                                class="w-24 rounded-lg border border-[#e6e0d3] px-2 py-1 text-right tabular-nums"
                                inputmode="numeric"
                                :value="school.learners_reached ?? ''"
                                :aria-label="`Learners reached, ${school.name}`"
                                @change="onNumber(school, 'learners_reached', $event)"
                            />
                        </td>
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ formatRate(school.reach_rate) }}</td>
                        <td class="px-3 py-2.5">
                            <select
                                class="rounded-full px-3 py-1 text-xs font-extrabold text-white"
                                :style="{ background: levelColors[school.flp_level] }"
                                :value="school.flp_level"
                                :aria-label="`FLP level, ${school.name}`"
                                @change="save(school, { flp_level: ($event.target as HTMLSelectElement).value })"
                            >
                                <option v-for="level in levels" :key="level.value" :value="level.value">
                                    {{ level.label }}
                                </option>
                            </select>
                        </td>
                        <td class="px-3 py-2.5">
                            <StatusPill :status="school.status" :label="school.status_label" />
                        </td>
                        <td class="px-3 py-2.5">
                            <input
                                type="checkbox"
                                class="size-5 accent-[#0a5c9e]"
                                :checked="school.ta_needed"
                                :aria-label="`TA needed, ${school.name}`"
                                @change="save(school, { ta_needed: ($event.target as HTMLInputElement).checked })"
                            />
                        </td>
                        <td class="px-3 py-2.5">
                            <Button type="button" variant="outline" size="sm" :data-test="`view-school-${school.id}`" @click="openSchool(school)">
                                View
                            </Button>
                        </td>
                        <td v-if="page.props.can.manageSchools" class="px-3 py-2.5">
                            <Button type="button" variant="outline" size="sm" @click="remove(school)">Remove</Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div
        v-if="selected"
        class="fixed inset-0 z-50 grid place-items-center bg-[#061424]/60 p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="selected.name"
        data-test="school-detail"
        @click.self="selected = null"
    >
        <div class="max-h-[85vh] w-full max-w-3xl overflow-auto rounded-2xl bg-[#fffdf8] p-5">
            <div class="mb-4 flex items-start justify-between gap-3">
                <h2 class="text-xl font-extrabold text-[#12304f]">{{ selected.name }}</h2>
                <Button type="button" variant="outline" @click="selected = null">Close</Button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-3 py-2">
                    <span class="text-xs text-[#5a6f86]">Cluster</span>
                    <b class="block">{{ selected.cluster_label }}</b>
                </div>
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-3 py-2">
                    <span class="text-xs text-[#5a6f86]">School head</span>
                    <b class="block">{{ selected.head_name ?? '—' }}</b>
                </div>
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-3 py-2">
                    <span class="text-xs text-[#5a6f86]">Cluster supervisor (PSDS)</span>
                    <b class="block">{{ selected.supervisor_name }}</b>
                </div>
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-3 py-2">
                    <span class="text-xs text-[#5a6f86]">FLP level</span>
                    <b class="block">{{ selected.flp_label }}</b>
                </div>
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-3 py-2">
                    <span class="text-xs text-[#5a6f86]">Total learners</span>
                    <b class="block tabular-nums">{{ formatNumber(selected.learners_total) }}</b>
                </div>
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-3 py-2">
                    <span class="text-xs text-[#5a6f86]">Learners reached</span>
                    <b class="block tabular-nums">{{ formatNumber(selected.learners_reached) }}</b>
                </div>
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-3 py-2">
                    <span class="text-xs text-[#5a6f86]">Reach rate</span>
                    <b class="block tabular-nums">{{ formatRate(selected.reach_rate) }}</b>
                </div>
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-3 py-2">
                    <span class="text-xs text-[#5a6f86]">Status · action</span>
                    <b class="block">
                        {{ selected.status_label }}<template v-if="statusActions[selected.status]"> — {{ statusActions[selected.status] }}</template>
                    </b>
                </div>
                <div class="rounded-xl border border-[#e6e0d3] bg-white px-3 py-2">
                    <span class="text-xs text-[#5a6f86]">TA needed</span>
                    <b class="block">{{ selected.ta_needed ? 'Yes' : 'No' }}</b>
                </div>
            </div>

            <h3 class="mt-5 mb-2 text-sm font-extrabold">
                Students by section ({{ formatNumber((selected.sections ?? []).reduce((sum, section) => sum + section.learners, 0)) }} tracked)
            </h3>
            <p v-if="(selected.sections ?? []).length === 0" class="text-sm text-[#5a6f86]">No sections recorded for this school yet.</p>
            <div v-else class="max-h-44 overflow-auto rounded-xl border border-[#e6e0d3]">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-[#5a6f86]">
                        <tr>
                            <th class="px-3 py-2">Section</th>
                            <th class="px-3 py-2 text-right">Learners</th>
                            <th class="px-3 py-2 text-right">With internet access</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="section in selected.sections" :key="section.id" class="border-t border-[#e6e0d3]" data-test="detail-section">
                            <td class="px-3 py-2">{{ section.name }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ section.learners }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ section.internet_percent }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3 class="mt-5 mb-2 text-sm font-extrabold">Teachers</h3>
            <p class="text-sm text-[#5a6f86]">No teacher records for this school yet.</p>
        </div>
    </div>
</template>
