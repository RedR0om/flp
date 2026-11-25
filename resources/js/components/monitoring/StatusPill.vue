<script setup lang="ts">
const props = defineProps<{
    status: string;
    label: string;
}>();

const tones: Record<string, string> = {
    ex: 'bg-[#c9ecd7] text-[#0b5f2f]',
    ok: 'bg-[#e0f4e8] text-[#0f7a3d]',
    na: 'bg-[#fdf0d6] text-[#a2600a]',
    hp: 'bg-[#ffe3cc] text-[#a8480a]',
    cr: 'bg-[#fde4e2] text-[#bf2a23]',
    none: 'bg-[#faf7f0] text-[#5a6f86]',
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap"
        :class="tones[props.status] ?? tones.none"
    >
        <span class="size-1.5 rounded-full bg-current" />
        {{ props.label }}
    </span>
</template>
