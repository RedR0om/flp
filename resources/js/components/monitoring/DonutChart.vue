<script setup lang="ts">
import { computed } from 'vue';
import type { StatusSegment } from '@/types/monitoring';

const props = defineProps<{
    segments: StatusSegment[];
    total: number;
}>();

const radius = 16;
const circumference = 2 * Math.PI * radius;

const arcs = computed(() => {
    const whole = props.segments.reduce((sum, segment) => sum + segment.count, 0) || 1;
    let offset = 0;

    return props.segments
        .filter((segment) => segment.count > 0)
        .map((segment) => {
            const length = (segment.count / whole) * circumference;
            const arc = {
                ...segment,
                dash: `${length} ${circumference - length}`,
                offset,
            };
            offset -= length;

            return arc;
        });
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-6">
        <svg viewBox="0 0 42 42" class="size-36 shrink-0" role="img" aria-label="Schools by reach status">
            <circle cx="21" cy="21" r="16" fill="none" stroke="#e6e0d3" stroke-width="6" />
            <circle
                v-for="arc in arcs"
                :key="arc.key"
                cx="21"
                cy="21"
                r="16"
                fill="none"
                :stroke="arc.color"
                stroke-width="6"
                :stroke-dasharray="arc.dash"
                :stroke-dashoffset="arc.offset"
                transform="rotate(-90 21 21)"
            />
            <text x="21" y="20" text-anchor="middle" class="fill-[#12304f] text-[5px] font-bold">
                {{ props.total }}
            </text>
            <text x="21" y="25" text-anchor="middle" class="fill-[#5a6f86] text-[3px]">
                schools
            </text>
        </svg>
        <ul class="grid min-w-48 flex-1 gap-2 text-sm">
            <li v-for="segment in segments" :key="segment.key" class="flex items-center gap-2">
                <span class="size-2.5 shrink-0 rounded-full" :style="{ background: segment.color }" />
                <span class="text-[#12304f]">{{ segment.label }}</span>
                <span class="ml-auto font-semibold tabular-nums">{{ segment.count }}</span>
            </li>
        </ul>
    </div>
</template>
