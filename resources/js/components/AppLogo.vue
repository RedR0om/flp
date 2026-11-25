<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const name = usePage().props.name;
</script>

<template>
    <div class="grid flex-1 text-left leading-tight">
        <span class="truncate text-[11px] font-semibold tracking-wide uppercase">Flexible Learning Program</span>
        <span class="truncate text-sm font-extrabold">{{ name }}</span>
    </div>
</template>
