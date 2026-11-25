<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    points: Array<{ date: string; rate: number | null }>;
}>();

const width = 420;
const height = 160;
const pad = 18;

const plotted = computed(() => {
    const known = props.points.filter((point) => point.rate !== null);
    if (known.length === 0) {
        return [];
    }

    const min = Math.min(80, ...known.map((point) => point.rate as number));
    const max = Math.max(100, ...known.map((point) => point.rate as number));
    const span = max - min || 1;

    return props.points.map((point, index) => {
        const x =
            props.points.length === 1
                ? width / 2
                : pad + (index / (props.points.length - 1)) * (width - pad * 2);
        const rate = point.rate ?? min;
        const y = pad + (1 - (rate - min) / span) * (height - pad * 2);

        return { ...point, x, y };
    });
});

const line = computed(() => plotted.value.map((point, index) => `${index === 0 ? 'M' : 'L'}${point.x},${point.y}`).join(' '));
</script>

<template>
    <div>
        <p v-if="plotted.length === 0" class="text-sm text-[#5a6f86]">No reach snapshots yet.</p>
        <svg v-else :viewBox="`0 0 ${width} ${height}`" class="h-40 w-full" role="img" aria-label="Reach rate trend">
            <path :d="line" fill="none" stroke="#0a5c9e" stroke-width="2.5" stroke-linejoin="round" />
            <g v-for="point in plotted" :key="point.date">
                <circle :cx="point.x" :cy="point.y" r="3.5" fill="#0a5c9e" />
                <text :x="point.x" :y="height - 2" text-anchor="middle" font-size="10" fill="#5a6f86">{{ point.date }}</text>
                <text :x="point.x" :y="point.y - 8" text-anchor="middle" font-size="11" font-weight="700" fill="#12304f">
                    {{ point.rate?.toFixed(1) }}%
                </text>
            </g>
        </svg>
        <p class="mt-2 text-xs text-[#5a6f86]">Division reach rate, saved when a school report changes. One point per day.</p>
    </div>
</template>
