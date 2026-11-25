<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import StatusPill from '@/components/monitoring/StatusPill.vue';
import { formatNumber, formatRate } from '@/types/monitoring';

type Count = { modality: string; learners_total: number; learners_reached: number };
type SchoolCounts = { id: number; name: string; counts: Count[] };
type Row = Count & { reach_rate: number | null; status: string; status_label: string };

const props = defineProps<{
    rows: Row[];
    totals: { learners_reached: number; learners_total: number; reach_rate: number | null };
    schools: SchoolCounts[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Modality Monitoring', href: '/modalities' }] },
});

const schoolId = ref(props.schools[0]?.id ?? 0);
const school = () => props.schools.find((item) => item.id === schoolId.value);

function save(count: Count, field: 'learners_total' | 'learners_reached', value: string) {
    const next = { ...count, [field]: value === '' ? 0 : Number(value) };
    router.patch('/modalities', { school_id: schoolId.value, ...next }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Modality Monitoring" />

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight">Modality Monitoring</h1>
                <p class="text-sm text-[#5a6f86]">Totals below are the sum of the schools you can see. Each school starts at zero.</p>
            </div>
            <label class="text-sm font-semibold text-[#5a6f86]">
                School
                <select v-model.number="schoolId" class="mt-1 block h-10 rounded-xl border border-[#e6e0d3] bg-white px-3 text-[#12304f]" aria-label="School">
                    <option v-for="item in schools" :key="item.id" :value="item.id">{{ item.name }}</option>
                </select>
            </label>
        </div>

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]">
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">Modality</th>
                        <th class="px-4 py-3">Learners reached</th>
                        <th class="px-4 py-3">Total learners</th>
                        <th class="px-4 py-3">Reach rate</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.modality" class="border-t border-[#e6e0d3]">
                        <td class="px-4 py-3 font-semibold">{{ row.modality }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ formatNumber(row.learners_reached) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ formatNumber(row.learners_total) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ formatRate(row.reach_rate) }}</td>
                        <td class="px-4 py-3"><StatusPill :status="row.status" :label="row.status_label" /></td>
                    </tr>
                    <tr class="border-t-2 border-[#0a5c9e] font-extrabold">
                        <td class="px-4 py-3">All modalities</td>
                        <td class="px-4 py-3 tabular-nums">{{ formatNumber(totals.learners_reached) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ formatNumber(totals.learners_total) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ formatRate(totals.reach_rate) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section v-if="school()" class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
            <h2 class="mb-3 text-sm font-extrabold">{{ school()?.name }}</h2>
            <div class="grid gap-3 md:grid-cols-2">
                <div v-for="count in school()?.counts" :key="count.modality" class="rounded-xl border border-[#e6e0d3] p-3">
                    <b class="block text-sm">{{ count.modality }}</b>
                    <div class="mt-2 flex gap-2">
                        <label class="text-xs text-[#5a6f86]">
                            Reached
                            <input
                                type="number"
                                min="0"
                                class="mt-1 h-9 w-full rounded-lg border border-[#e6e0d3] px-2"
                                :value="count.learners_reached"
                                :aria-label="`${count.modality} reached`"
                                @change="save(count, 'learners_reached', ($event.target as HTMLInputElement).value)"
                            />
                        </label>
                        <label class="text-xs text-[#5a6f86]">
                            Total
                            <input
                                type="number"
                                min="0"
                                class="mt-1 h-9 w-full rounded-lg border border-[#e6e0d3] px-2"
                                :value="count.learners_total"
                                :aria-label="`${count.modality} total`"
                                @change="save(count, 'learners_total', ($event.target as HTMLInputElement).value)"
                            />
                        </label>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
