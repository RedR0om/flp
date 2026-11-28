<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Password reset is closed',
        description: 'Ask the division administrator to set a new password',
    },
});
</script>

<template>
    <Head title="Reset password" />

    <div class="text-center text-sm text-[#5a6f86]">
        <TextLink :href="login()">Back to sign in</TextLink>
    </div>
</template>
