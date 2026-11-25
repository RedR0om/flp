<script setup lang="ts">
import { computed } from 'vue';
import type { SchoolRow } from '@/types/monitoring';
import { taguigMapSvg } from '@/components/monitoring/taguigMap';

const props = defineProps<{
    schools: SchoolRow[];
}>();

const markup = computed(() => taguigMapSvg(props.schools));
</script>

<template>
    <div data-test="taguig-map" v-html="markup" />
</template>
