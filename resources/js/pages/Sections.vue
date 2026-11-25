<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import DonutChart from '@/components/monitoring/DonutChart.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { StatusSegment } from '@/types/monitoring';

type SchoolOption = {
    id: number;
    name: string;
    cluster_label: string;
    head_name: string | null;
    supervisor_name: string;
};

type SectionSummary = {
    id: number;
    name: string;
    school_name: string;
    cluster_label: string;
    head_name: string | null;
    supervisor_name: string;
    learners: number;
    with_access: number;
};

type Learner = { id: number; net: string; sim: string | null };

const props = defineProps<{
    sections: SectionSummary[];
    selected: (SectionSummary & { stats: { internet: StatusSegment[]; sim: StatusSegment[]; total: number; access: number; no_access: number }; learners: Learner[] }) | null;
    schools: SchoolOption[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Section Details', href: '/sections' }] },
});

const form = useForm({
    name: '',
    school_id: props.schools[0]?.id ?? '',
});

function select(id: number) {
    router.get('/sections', { section: id }, { preserveState: true, preserveScroll: true });
}

function addLearner() {
    if (!props.selected) {
        return;
    }

    router.post(`/sections/${props.selected.id}/learners`, {}, { preserveScroll: true });
}

function updateLearner(learner: Learner, patch: Partial<Learner>) {
    router.patch(`/learners/${learner.id}`, { net: learner.net, sim: learner.sim, ...patch }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Section Details" />

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight">Section Details</h1>
                <p class="text-sm text-[#5a6f86]">Add a section, then record each learner’s internet access. Teacher names are not stored.</p>
            </div>
            <form class="flex flex-wrap items-center gap-2" @submit.prevent="form.post('/sections')">
                <select v-model="form.school_id" class="h-10 rounded-xl border border-[#e6e0d3] bg-white px-3" aria-label="School">
                    <option v-for="school in schools" :key="school.id" :value="school.id">{{ school.name }}</option>
                </select>
                <Input v-model="form.name" placeholder="Section name" aria-label="Section name" class="w-48" required />
                <Button type="submit" :disabled="form.processing">Add section</Button>
            </form>
        </div>

        <div class="grid gap-4 lg:grid-cols-[280px_1fr]">
            <aside class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-3">
                <p v-if="sections.length === 0" class="px-2 py-6 text-sm text-[#5a6f86]">No sections yet.</p>
                <button
                    v-for="section in sections"
                    :key="section.id"
                    type="button"
                    class="mb-1 w-full rounded-xl px-3 py-2 text-left"
                    :class="selected?.id === section.id ? 'bg-[#e7f4fb]' : 'hover:bg-[#f6f3ec]'"
                    @click="select(section.id)"
                >
                    <b class="block text-sm">{{ section.name }}</b>
                    <span class="text-xs text-[#5a6f86]">{{ section.school_name }} · {{ section.learners }} learners</span>
                </button>
            </aside>

            <section v-if="selected" class="flex flex-col gap-4">
                <div class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold">{{ selected.name }}</h2>
                            <p class="text-sm text-[#5a6f86]">
                                {{ selected.school_name }} · {{ selected.cluster_label }} · Head: {{ selected.head_name || '—' }} · PSDS:
                                {{ selected.supervisor_name }}
                            </p>
                        </div>
                        <Button variant="outline" type="button" @click="router.delete(`/sections/${selected.id}`)">Remove section</Button>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <article class="rounded-xl bg-[#f6f3ec] px-3 py-2">
                            <span class="text-xs text-[#5a6f86]">Learners</span>
                            <b class="block text-2xl">{{ selected.stats.total }}</b>
                        </article>
                        <article class="rounded-xl bg-[#f6f3ec] px-3 py-2">
                            <span class="text-xs text-[#5a6f86]">With access</span>
                            <b class="block text-2xl">{{ selected.stats.access }}%</b>
                        </article>
                        <article class="rounded-xl bg-[#f6f3ec] px-3 py-2">
                            <span class="text-xs text-[#5a6f86]">No access</span>
                            <b class="block text-2xl">{{ selected.stats.no_access }}%</b>
                        </article>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
                        <h3 class="mb-3 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">Internet access</h3>
                        <DonutChart :segments="selected.stats.internet" :total="selected.stats.total" />
                    </article>
                    <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
                        <h3 class="mb-3 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">Mobile SIM</h3>
                        <DonutChart :segments="selected.stats.sim" :total="selected.stats.total" />
                    </article>
                </div>

                <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">Learners</h3>
                        <Button type="button" @click="addLearner">Add learner</Button>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs text-[#5a6f86]">
                            <tr>
                                <th class="px-2 py-2">#</th>
                                <th class="px-2 py-2">Internet</th>
                                <th class="px-2 py-2">SIM</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="selected.learners.length === 0">
                                <td colspan="4" class="px-2 py-6 text-center text-[#5a6f86]">No learners in this section yet.</td>
                            </tr>
                            <tr v-for="(learner, index) in selected.learners" :key="learner.id" class="border-t border-[#e6e0d3]">
                                <td class="px-2 py-2">{{ index + 1 }}</td>
                                <td class="px-2 py-2">
                                    <select
                                        class="h-9 rounded-lg border border-[#e6e0d3] bg-white px-2"
                                        :value="learner.net"
                                        :aria-label="`Internet for learner ${index + 1}`"
                                        @change="updateLearner(learner, { net: ($event.target as HTMLSelectElement).value })"
                                    >
                                        <option>Mobile Data</option>
                                        <option>Home WiFi / Broadband</option>
                                        <option>None</option>
                                    </select>
                                </td>
                                <td class="px-2 py-2">
                                    <select
                                        class="h-9 rounded-lg border border-[#e6e0d3] bg-white px-2"
                                        :value="learner.sim ?? ''"
                                        :disabled="learner.net !== 'Mobile Data'"
                                        :aria-label="`SIM for learner ${index + 1}`"
                                        @change="updateLearner(learner, { sim: ($event.target as HTMLSelectElement).value || null })"
                                    >
                                        <option value="">—</option>
                                        <option>Globe</option>
                                        <option>Smart</option>
                                        <option>DITO</option>
                                    </select>
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <button type="button" class="text-xs font-semibold text-[#bf2a23]" @click="router.delete(`/learners/${learner.id}`)">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </article>
            </section>
        </div>
    </div>
</template>
