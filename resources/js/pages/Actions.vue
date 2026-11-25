<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type RecordRow = { id: number; school: string; concern: string; action: string; responsible: string; status: string };
type SchoolOption = { id: number; name: string };

const props = defineProps<{ records: RecordRow[]; schools: SchoolOption[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Action / Technical Assistance', href: '/actions' }] },
});

const tones: Record<string, string> = {
    Requested: 'bg-[#fdf0d6] text-[#a2600a]',
    Ongoing: 'bg-[#e7f4fb] text-[#0a5c9e]',
    Resolved: 'bg-[#c9ecd7] text-[#0b5f2f]',
};

const form = useForm({
    school_id: props.schools[0]?.id ?? '',
    concern: '',
    action: '',
    responsible: '',
    status: 'Requested',
});
</script>

<template>
    <Head title="Action / Technical Assistance" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Action / Technical Assistance</h1>
            <p class="text-sm text-[#5a6f86]">Click a status to move it from Requested to Ongoing to Resolved.</p>
        </div>

        <form class="grid gap-2 rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-4 md:grid-cols-2" @submit.prevent="form.post('/actions', { preserveScroll: true, onSuccess: () => form.reset('concern', 'action', 'responsible') })">
            <select v-model="form.school_id" class="h-10 rounded-xl border border-[#e6e0d3] bg-white px-3" aria-label="School">
                <option v-for="school in schools" :key="school.id" :value="school.id">{{ school.name }}</option>
            </select>
            <Input v-model="form.concern" placeholder="Concern" aria-label="Concern" required />
            <Input v-model="form.action" placeholder="Action assigned" aria-label="Action assigned" required />
            <div class="flex gap-2">
                <Input v-model="form.responsible" placeholder="Responsible" aria-label="Responsible" required />
                <select v-model="form.status" class="h-10 rounded-xl border border-[#e6e0d3] bg-white px-3" aria-label="Status">
                    <option>Requested</option>
                    <option>Ongoing</option>
                    <option>Resolved</option>
                </select>
                <Button type="submit" :disabled="form.processing">Add</Button>
            </div>
        </form>

        <section class="overflow-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]">
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Concern</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Responsible</th>
                        <th class="px-4 py-3">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="records.length === 0">
                        <td colspan="6" class="px-4 py-6 text-center text-[#5a6f86]">No actions yet.</td>
                    </tr>
                    <tr v-for="record in records" :key="record.id" class="border-t border-[#e6e0d3]">
                        <td class="px-4 py-3 font-semibold">{{ record.school }}</td>
                        <td class="px-4 py-3">{{ record.concern }}</td>
                        <td class="px-4 py-3">{{ record.action }}</td>
                        <td class="px-4 py-3">{{ record.responsible }}</td>
                        <td class="px-4 py-3">
                            <button
                                type="button"
                                class="rounded-lg px-2 py-1 text-xs font-extrabold"
                                :class="tones[record.status]"
                                @click="router.patch(`/actions/${record.id}`, {}, { preserveScroll: true })"
                            >
                                {{ record.status }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="text-xs font-semibold text-[#bf2a23]" @click="router.delete(`/actions/${record.id}`)">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
