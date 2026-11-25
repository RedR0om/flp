<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Registration is closed',
        description: 'Accounts are created by the division office',
    },
});
</script>

<template>
    <Head title="Register" />

    <div class="text-center text-sm text-muted-foreground">
        <TextLink :href="login()">Back to log in</TextLink>
    </div>
</template>
