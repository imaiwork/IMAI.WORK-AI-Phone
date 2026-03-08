<template>
    <div class="flex items-center gap-2">
        <div class="flex flex-col items-end gap-1">
            <div class="flex items-center gap-1.5">
                <span class="text-[11px] font-black" :class="isNearFull ? 'text-red-500' : 'text-slate-500'">
                    {{ formatSeconds(remaining) }} 剩余
                </span>
                <span class="text-[10px] text-slate-300 font-medium">/ {{ formatSeconds(max) }}</span>
            </div>
            <div class="w-[120px] h-1.5 bg-slate-100 rounded-full overflow-hidden">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="barColor"
                    :style="{ width: `${usedPercent}%` }"></div>
            </div>
        </div>
        <div
            class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
            :class="isNearFull ? 'bg-red-50 text-red-400' : 'bg-slate-100 text-slate-400'">
            <Icon name="el-icon-Timer" :size="14" />
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{ used: number; max: number }>();

const remaining = computed(() => Math.max(0, props.max - props.used));
const usedPercent = computed(() => Math.min(100, (props.used / props.max) * 100));
const isNearFull = computed(() => usedPercent.value >= 80);

const barColor = computed(() => {
    if (usedPercent.value >= 90) return "bg-red-500";
    if (usedPercent.value >= 80) return "bg-orange-400";
    return "bg-primary";
});

const formatSeconds = (s: number) => {
    const m = Math.floor(s / 60);
    const sec = Math.round(s % 60);
    return m > 0 ? `${m}分${sec > 0 ? sec + "秒" : ""}` : `${sec}秒`;
};
</script>
