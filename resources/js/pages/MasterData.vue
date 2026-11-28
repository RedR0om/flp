<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type DirectorySchool = {
    id: number;
    name: string;
    head_name: string | null;
    cluster: string;
    supervisor: string;
    supervisor_title: string;
};

const props = defineProps<{ schools: DirectorySchool[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Master Data', href: '/master-data' }] },
});

const search = ref('');

const rows = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (query === '') {
        return props.schools;
    }

    return props.schools.filter((school) => `${school.name} ${school.cluster} ${school.head_name ?? ''}`.toLowerCase().includes(query));
});
</script>

<template>
    <Head title="Master Data" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#0a5c9e]">Master Data</h1>
            <p class="text-sm text-[#5a6f86]">Schools, clusters, and school heads already stored for this division.</p>
        </div>

        <p class="rounded-xl border border-[#f3e2b8] bg-[#fff8ea] px-4 py-3 text-sm text-[#7a5b16]">
            Official public school ID numbers are not in the current records, so that column is blank. Cluster assignments are the ones stored with each school.
        </p>

        <input
            v-model="search"
            type="search"
            placeholder="Search school or cluster"
            aria-label="Search schools"
            class="h-11 max-w-md rounded-xl border border-[#e6e0d3] bg-white px-3"
        />

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]" data-test="master-data">
            <h2 class="border-b-2 border-[#0a5c9e] px-4 py-3 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">School directory</h2>
            <table class="w-full min-w-[720px] text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Public ID</th>
                        <th class="px-4 py-3">Cluster</th>
                        <th class="px-4 py-3">School head</th>
                        <th class="px-4 py-3">Supervisor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="rows.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-[#5a6f86]">No schools are in view.</td>
                    </tr>
                    <tr v-for="school in rows" :key="school.id" class="border-t border-[#e6e0d3]" data-test="directory-row">
                        <td class="px-4 py-2.5 font-medium">{{ school.name }}</td>
                        <td class="px-4 py-2.5 text-[#5a6f86]">—</td>
                        <td class="px-4 py-2.5">{{ school.cluster }}</td>
                        <td class="px-4 py-2.5">{{ school.head_name ?? '—' }}</td>
                        <td class="px-4 py-2.5">
                            {{ school.supervisor }}
                            <span class="block text-xs text-[#5a6f86]">{{ school.supervisor_title }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
