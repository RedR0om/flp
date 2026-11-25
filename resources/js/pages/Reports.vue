<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatNumber, formatRate, type SchoolRow } from '@/types/monitoring';

type Totals = { schools: number; learners: number; reached: number; rate: number | null; issues: number; ta: number };
type ActionRow = { school: string; concern: string; action: string; responsible: string; status: string };

const props = defineProps<{ totals: Totals; schools: SchoolRow[]; actions: ActionRow[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Reports & Export', href: '/reports' }] },
});

const situation = ref('');
const findings = ref('');
const interventions = ref('');
const nextSteps = ref('');
const preparedBy = ref('');
const notedBy = ref('');
const reviewedBy = ref('');

function narrative(): string {
    const rows = props.schools
        .map(
            (school) =>
                `<tr><td>${school.name}</td><td>${school.cluster_label}</td><td>${school.learners_total ?? '—'}</td><td>${school.learners_reached ?? '—'}</td><td>${formatRate(school.reach_rate)}</td><td>${school.flp_level}</td><td>${school.status_label}</td></tr>`,
        )
        .join('');

    return `<html><head><meta charset="utf-8"><title>FLP narrative report</title>
        <style>body{font-family:Arial,sans-serif;color:#12304f;padding:32px}h1{color:#0a5c9e}table{border-collapse:collapse;width:100%;font-size:12px}td,th{border:1px solid #e6e0d3;padding:6px;text-align:left}h2{font-size:14px;color:#0a5c9e;border-bottom:2px solid #0a5c9e}</style></head><body>
        <h1>Flexible Learning Program — Narrative Report</h1>
        <p>Schools Division of Taguig City and Pateros</p>
        <p>Schools in view: ${props.totals.schools}. Learners: ${formatNumber(props.totals.learners)}. Reached: ${formatNumber(props.totals.reached)} (${formatRate(props.totals.rate)}). Issues: ${props.totals.issues}. TA flagged: ${props.totals.ta}.</p>
        <h2>I. Situation and Learning Continuity Response</h2><p>${situation.value || '—'}</p>
        <h2>II. Learner Reach and Participation</h2>
        <table><thead><tr><th>School</th><th>Cluster</th><th>Learners</th><th>Reached</th><th>Rate</th><th>4H</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table>
        <h2>III. Major Findings and Challenges</h2><p>${findings.value || '—'}</p>
        <h2>IV. Interventions and Actions Taken</h2><p>${interventions.value || '—'}</p>
        <h2>V. Next Steps and Recommendations</h2><p>${nextSteps.value || '—'}</p>
        <p>Prepared by: ${preparedBy.value || '—'}<br>Noted by: ${notedBy.value || '—'}<br>Reviewed by: ${reviewedBy.value || '—'}</p>
        </body></html>`;
}

function printReport() {
    const popup = window.open('', '_blank');
    if (!popup) {
        return;
    }
    popup.document.write(narrative());
    popup.document.close();
    popup.focus();
    popup.print();
}

function downloadDoc() {
    const blob = new Blob([narrative()], { type: 'application/msword' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'flp-narrative-report.doc';
    link.click();
    URL.revokeObjectURL(link.href);
}
</script>

<template>
    <Head title="Reports & Export" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Reports & Export</h1>
            <p class="text-sm text-[#5a6f86]">Downloads include only the schools in your scope.</p>
        </div>

        <section class="grid gap-3 sm:grid-cols-3">
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-4">
                <span class="text-xs text-[#5a6f86]">Schools</span>
                <b class="block text-3xl">{{ formatNumber(totals.schools) }}</b>
            </article>
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-4">
                <span class="text-xs text-[#5a6f86]">Reach rate</span>
                <b class="block text-3xl">{{ formatRate(totals.rate) }}</b>
            </article>
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-4">
                <span class="text-xs text-[#5a6f86]">Open actions</span>
                <b class="block text-3xl">{{ actions.filter((action) => action.status !== 'Resolved').length }}</b>
            </article>
        </section>

        <section class="flex flex-wrap gap-2">
            <a href="/reports/schools.csv" class="inline-flex h-10 items-center rounded-xl bg-[#0a5c9e] px-4 text-sm font-semibold text-white">Download school status (CSV)</a>
            <a href="/reports/actions.csv" class="inline-flex h-10 items-center rounded-xl bg-[#0a5c9e] px-4 text-sm font-semibold text-white">Download actions (CSV)</a>
        </section>

        <section class="grid gap-3 rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
            <h2 class="text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">Narrative report</h2>
            <label class="text-sm font-semibold">I. Situation and learning continuity response<textarea v-model="situation" class="mt-1 min-h-20 w-full rounded-xl border border-[#e6e0d3] p-3 font-normal" /></label>
            <label class="text-sm font-semibold">III. Major findings and challenges<textarea v-model="findings" class="mt-1 min-h-20 w-full rounded-xl border border-[#e6e0d3] p-3 font-normal" /></label>
            <label class="text-sm font-semibold">IV. Interventions and actions taken<textarea v-model="interventions" class="mt-1 min-h-20 w-full rounded-xl border border-[#e6e0d3] p-3 font-normal" /></label>
            <label class="text-sm font-semibold">V. Next steps and recommendations<textarea v-model="nextSteps" class="mt-1 min-h-20 w-full rounded-xl border border-[#e6e0d3] p-3 font-normal" /></label>
            <div class="grid gap-2 md:grid-cols-3">
                <label class="text-sm font-semibold">Prepared by<input v-model="preparedBy" class="mt-1 h-10 w-full rounded-xl border border-[#e6e0d3] px-3 font-normal" /></label>
                <label class="text-sm font-semibold">Noted by<input v-model="notedBy" class="mt-1 h-10 w-full rounded-xl border border-[#e6e0d3] px-3 font-normal" /></label>
                <label class="text-sm font-semibold">Reviewed by<input v-model="reviewedBy" class="mt-1 h-10 w-full rounded-xl border border-[#e6e0d3] px-3 font-normal" /></label>
            </div>
            <div class="flex gap-2">
                <button type="button" class="h-10 rounded-xl bg-[#f2663f] px-4 text-sm font-semibold text-white" @click="printReport">Print</button>
                <button type="button" class="h-10 rounded-xl border border-[#e6e0d3] px-4 text-sm font-semibold" @click="downloadDoc">Download .doc</button>
            </div>
        </section>
    </div>
</template>
