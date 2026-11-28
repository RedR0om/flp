<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';

type ActivityRow = {
    id: number;
    occurred_at: string;
    summary: string;
    detail: string | null;
    school: string | null;
    actor: string | null;
    read: boolean;
};

const props = defineProps<{
    activities: ActivityRow[];
    unread: number;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Notifications & Activity History', href: '/activity' }] },
});

const search = ref('');

const rows = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (query === '') {
        return props.activities;
    }

    return props.activities.filter((activity) =>
        `${activity.summary} ${activity.detail ?? ''} ${activity.school ?? ''}`.toLowerCase().includes(query),
    );
});

function markRead(activity: ActivityRow) {
    router.post(`/activity/${activity.id}/read`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications & Activity History" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#0a5c9e]">Notifications & Activity History</h1>
            <p class="text-sm text-[#5a6f86]">Records already stored for the schools you can see, plus changes made in this app after this screen was added.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-3">
                <span class="text-sm text-[#5a6f86]">Visible activities</span>
                <b class="mt-1 block text-3xl tabular-nums" data-test="activity-count">{{ activities.length }}</b>
            </article>
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-3">
                <span class="text-sm text-[#5a6f86]">Unread</span>
                <b class="mt-1 block text-3xl tabular-nums">{{ unread }}</b>
            </article>
            <article class="rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] px-4 py-3">
                <span class="text-sm text-[#5a6f86]">External messages</span>
                <b class="mt-1 block text-3xl tabular-nums">0</b>
                <span class="text-xs text-[#5a6f86]">No email or SMS is sent from this app.</span>
            </article>
        </div>

        <input
            v-model="search"
            type="search"
            placeholder="Search activity or school"
            aria-label="Search activity"
            class="h-11 max-w-md rounded-xl border border-[#e6e0d3] bg-white px-3"
        />

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]" data-test="activity-list">
            <h2 class="border-b-2 border-[#0a5c9e] px-4 py-3 text-xs font-extrabold tracking-wide text-[#0a5c9e] uppercase">Activity timeline</h2>
            <table class="w-full min-w-[760px] text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Activity</th>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Read</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="rows.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-[#5a6f86]">No activity is stored for the schools you can see.</td>
                    </tr>
                    <tr v-for="activity in rows" :key="activity.id" class="border-t border-[#e6e0d3]" data-test="activity-row">
                        <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">{{ activity.occurred_at }}</td>
                        <td class="px-4 py-2.5">
                            <b class="block">{{ activity.summary }}</b>
                            <span v-if="activity.detail" class="text-[#5a6f86]">{{ activity.detail }}</span>
                            <span v-if="activity.actor" class="block text-xs text-[#5a6f86]">{{ activity.actor }}</span>
                        </td>
                        <td class="px-4 py-2.5">{{ activity.school ?? 'Division' }}</td>
                        <td class="px-4 py-2.5">
                            <span v-if="activity.read" class="text-xs font-bold text-[#0b5f2f]">Read</span>
                            <Button v-else type="button" variant="outline" size="sm" @click="markRead(activity)">Mark read</Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
