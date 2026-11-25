<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import DonutChart from '@/components/monitoring/DonutChart.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { StatusSegment } from '@/types/monitoring';

type RecordRow = { id: number; school: string; finding: string; severity: string };
type SchoolOption = { id: number; name: string };

const props = defineProps<{
    records: RecordRow[];
    segments: StatusSegment[];
    schools: SchoolOption[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Validation / Home Visitation', href: '/validations' }] },
});

const labels: Record<string, string> = {
    ok: 'Compliant',
    minor: 'Minor concern',
    major: 'Significant concern',
};

const tones: Record<string, string> = {
    ok: 'bg-[#c9ecd7] text-[#0b5f2f]',
    minor: 'bg-[#fdf0d6] text-[#a2600a]',
    major: 'bg-[#fde4e2] text-[#bf2a23]',
};

const form = useForm({
    school_id: props.schools[0]?.id ?? '',
    finding: '',
    severity: 'minor',
});
</script>

<template>
    <Head title="Validation / Home Visitation" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Validation / Home Visitation</h1>
            <p class="text-sm text-[#5a6f86]">Findings from reach validation and home visitation, for the schools you can see.</p>
        </div>

        <section class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
            <DonutChart :segments="segments" :total="records.length" />
        </section>

        <form class="grid gap-2 rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-4 md:grid-cols-[1fr_2fr_180px_auto]" @submit.prevent="form.post('/validations', { preserveScroll: true, onSuccess: () => form.reset('finding') })">
            <select v-model="form.school_id" class="h-10 rounded-xl border border-[#e6e0d3] bg-white px-3" aria-label="School">
                <option v-for="school in schools" :key="school.id" :value="school.id">{{ school.name }}</option>
            </select>
            <Input v-model="form.finding" placeholder="Finding" aria-label="Finding" required />
            <select v-model="form.severity" class="h-10 rounded-xl border border-[#e6e0d3] bg-white px-3" aria-label="Severity">
                <option value="ok">Compliant</option>
                <option value="minor">Minor concern</option>
                <option value="major">Significant concern</option>
            </select>
            <Button type="submit" :disabled="form.processing">Add finding</Button>
        </form>

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]">
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Finding</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="records.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-[#5a6f86]">No findings yet.</td>
                    </tr>
                    <tr v-for="record in records" :key="record.id" class="border-t border-[#e6e0d3]">
                        <td class="px-4 py-3 font-semibold">{{ record.school }}</td>
                        <td class="px-4 py-3">{{ record.finding }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-lg px-2 py-1 text-xs font-extrabold" :class="tones[record.severity]">{{ labels[record.severity] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="text-xs font-semibold text-[#bf2a23]" @click="router.delete(`/validations/${record.id}`)">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
