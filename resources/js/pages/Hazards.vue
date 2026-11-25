<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { levelColors, type FlpLevelOption, type SchoolRow } from '@/types/monitoring';

type Hazard = { condition: string; level: string; label: string; description: string };

const props = defineProps<{
    hazards: Hazard[];
    current: Hazard;
    schools: SchoolRow[];
    can_set_division: boolean;
    levels: FlpLevelOption[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: '4H Hazard Mapping', href: '/hazards' }] },
});

const condition = ref(props.current.condition);
const selected = computed(() => props.hazards.find((hazard) => hazard.condition === condition.value) ?? props.current);

function saveCondition() {
    router.patch('/hazards', { condition: condition.value }, { preserveScroll: true });
}

function apply() {
    router.post('/hazards/apply', { condition: condition.value }, { preserveScroll: true });
}

function setLevel(school: SchoolRow, level: string) {
    router.patch(
        `/schools/${school.id}/report`,
        {
            learners_total: school.learners_total,
            learners_reached: school.learners_reached,
            flp_level: level,
            ta_needed: school.ta_needed,
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="4H Hazard Mapping" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">4H Hazard Mapping</h1>
            <p class="text-sm text-[#5a6f86]">The division condition maps to a 4H level. Apply sets that level on the schools you can edit.</p>
        </div>

        <section class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-5">
            <label class="block text-sm font-semibold">
                Condition
                <select v-model="condition" class="mt-1 h-11 w-full rounded-xl border border-[#e6e0d3] bg-white px-3" aria-label="Hazard condition" :disabled="!can_set_division" @change="saveCondition">
                    <option v-for="hazard in hazards" :key="hazard.condition" :value="hazard.condition">{{ hazard.condition }}</option>
                </select>
            </label>
            <p class="mt-3 text-sm">
                Mapped level
                <span class="ml-2 inline-flex rounded-lg px-2 py-0.5 text-xs font-extrabold text-white" :style="{ background: levelColors[selected.level] }">
                    {{ selected.label }}
                </span>
            </p>
            <p class="mt-1 text-sm text-[#5a6f86]">{{ selected.description }}</p>
            <button type="button" class="mt-4 h-10 rounded-xl bg-[#f2663f] px-4 text-sm font-semibold text-white" @click="apply">
                Apply {{ selected.level }} to schools in view
            </button>
        </section>

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]">
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Cluster</th>
                        <th class="px-4 py-3">Current level</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="school in schools" :key="school.id" class="border-t border-[#e6e0d3]">
                        <td class="px-4 py-3 font-semibold">{{ school.name }}</td>
                        <td class="px-4 py-3">{{ school.cluster_label }}</td>
                        <td class="px-4 py-3">
                            <select
                                class="h-9 rounded-lg border border-[#e6e0d3] bg-white px-2"
                                :value="school.flp_level"
                                :aria-label="`Level for ${school.name}`"
                                @change="setLevel(school, ($event.target as HTMLSelectElement).value)"
                            >
                                <option v-for="level in levels" :key="level.value" :value="level.value">{{ level.label }}</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
