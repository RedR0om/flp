<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type RoleOption = {
    value: string;
    label: string;
    needs_cluster: boolean;
};

type ClusterOption = {
    id: number;
    number: number;
};

type Account = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    role_label: string;
    cluster_id: number | null;
    cluster_label: string | null;
};

const props = defineProps<{
    accounts: Account[];
    roles: RoleOption[];
    clusters: ClusterOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: '/users' }],
    },
});

const page = usePage<{ auth: { user: { id: number } } }>();
const showForm = ref(false);
const editing = ref<Account | null>(null);

const form = useForm({
    name: '',
    email: '',
    role: '',
    cluster_id: '' as string | number,
    password: '',
    password_confirmation: '',
});

const needsCluster = computed(() => props.roles.find((role) => role.value === form.role)?.needs_cluster ?? false);
const editingSelf = computed(() => editing.value?.id === page.props.auth.user.id);

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
}

function openEdit(account: Account) {
    editing.value = account;
    form.clearErrors();
    form.name = account.name;
    form.email = account.email;
    form.role = account.role ?? '';
    form.cluster_id = account.cluster_id ?? '';
    form.password = '';
    form.password_confirmation = '';
    showForm.value = true;
}

function cancel() {
    showForm.value = false;
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function save() {
    const options = {
        preserveScroll: true,
        onSuccess: () => cancel(),
    };

    if (editing.value) {
        form.patch(`/users/${editing.value.id}`, options);
        return;
    }

    form.post('/users', options);
}

function remove(account: Account) {
    if (!confirm(`Remove ${account.name}?`)) {
        return;
    }

    router.delete(`/users/${account.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#0a5c9e]">Users</h1>
                <p class="text-sm text-[#5a6f86]">
                    Create an account and choose the role. A cluster is required for a PSDS, school head, FLP focal, or OIC.
                </p>
            </div>
            <Button type="button" data-test="add-user" @click="showForm ? cancel() : openCreate()">
                {{ showForm && !editing ? 'Close' : 'Add user' }}
            </Button>
        </div>

        <form
            v-if="showForm"
            class="grid gap-3 rounded-2xl border border-[#e6e0d3] bg-[#fffdf8] p-4 md:grid-cols-2"
            @submit.prevent="save"
        >
            <div class="grid gap-1">
                <Label for="user-name">Name</Label>
                <Input id="user-name" v-model="form.name" required data-test="user-name" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-1">
                <Label for="user-email">Email</Label>
                <Input id="user-email" v-model="form.email" type="email" required data-test="user-email" />
                <InputError :message="form.errors.email" />
            </div>
            <div class="grid gap-1">
                <Label for="user-role">Role</Label>
                <select
                    id="user-role"
                    v-model="form.role"
                    required
                    :disabled="editingSelf"
                    data-test="user-role"
                    class="h-9 rounded-md border border-[#e6e0d3] bg-white px-3 text-sm disabled:opacity-70"
                >
                    <option value="" disabled>Select role</option>
                    <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                </select>
                <InputError :message="form.errors.role" />
            </div>
            <div class="grid gap-1">
                <Label for="user-cluster">Cluster</Label>
                <select
                    id="user-cluster"
                    v-model="form.cluster_id"
                    :required="needsCluster"
                    :disabled="!needsCluster"
                    data-test="user-cluster"
                    class="h-9 rounded-md border border-[#e6e0d3] bg-white px-3 text-sm disabled:opacity-70"
                >
                    <option value="">{{ needsCluster ? 'Select cluster' : 'Not used for this role' }}</option>
                    <option v-for="cluster in clusters" :key="cluster.id" :value="cluster.id">
                        Cluster {{ cluster.number }}
                    </option>
                </select>
                <InputError :message="form.errors.cluster_id" />
            </div>
            <div class="grid gap-1">
                <Label for="user-password">Password</Label>
                <Input
                    id="user-password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    :required="!editing"
                    data-test="user-password"
                    :placeholder="editing ? 'Leave blank to keep the current password' : 'At least 8 characters'"
                />
                <InputError :message="form.errors.password" />
            </div>
            <div class="grid gap-1">
                <Label for="user-password-confirmation">Confirm password</Label>
                <Input
                    id="user-password-confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    :required="!editing && form.password !== ''"
                    data-test="user-password-confirmation"
                />
            </div>
            <div class="flex items-center gap-2 md:col-span-2">
                <Button type="submit" :disabled="form.processing" data-test="save-user">
                    {{ editing ? 'Save changes' : 'Create account' }}
                </Button>
                <Button type="button" variant="outline" @click="cancel">Cancel</Button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-[#e6e0d3] bg-[#fffdf8]">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="text-left text-xs text-[#5a6f86]">
                    <tr>
                        <th class="px-3 py-3">Name</th>
                        <th class="px-3 py-3">Email</th>
                        <th class="px-3 py-3">Role</th>
                        <th class="px-3 py-3">Cluster</th>
                        <th class="px-3 py-3" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="account in accounts" :key="account.id" class="border-t border-[#e6e0d3]" data-test="user-row">
                        <td class="px-3 py-2.5 font-medium">{{ account.name }}</td>
                        <td class="px-3 py-2.5">{{ account.email }}</td>
                        <td class="px-3 py-2.5">{{ account.role_label }}</td>
                        <td class="px-3 py-2.5">{{ account.cluster_label ?? '—' }}</td>
                        <td class="px-3 py-2.5 text-right">
                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" :data-test="`edit-user-${account.id}`" @click="openEdit(account)">
                                    Edit
                                </Button>
                                <Button
                                    v-if="account.id !== page.props.auth.user.id"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :data-test="`remove-user-${account.id}`"
                                    @click="remove(account)"
                                >
                                    Remove
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
