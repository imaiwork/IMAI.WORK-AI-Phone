<template>
    <!-- 分组 tab：0 = 全部。分组由后台维护，没有可用曲目的分组不会下发，因此这里直接渲染 -->
    <div v-if="tabs.length > 1" class="flex items-center gap-2 overflow-x-auto pb-1 shrink-0">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            class="h-7 shrink-0 rounded-full px-3 text-[12px] font-black transition-colors"
            :class="
                Number(modelValue) === tab.id
                    ? 'bg-primary text-white'
                    : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
            "
            @click="emit('update:modelValue', tab.id)">
            {{ tab.name }}
        </button>
    </div>
</template>

<script setup lang="ts">
/** 选曲弹窗里的分组筛选条：全部 + 各分组 */
const props = withDefaults(
    defineProps<{
        groups?: Array<{ id: number | string; name: string }>;
        /** 0 = 全部 */
        modelValue?: number;
    }>(),
    {
        groups: () => [],
        modelValue: 0,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: number): void;
}>();

const tabs = computed(() => [
    { id: 0, name: "全部" },
    ...props.groups
        .map((item) => ({ id: Number(item.id) || 0, name: String(item.name || "") }))
        .filter((item) => item.id > 0 && item.name !== ""),
]);

// 后台删了分组后重新打开弹窗，选中项可能已不存在，回落到「全部」避免列表空白
watch(
    () => props.groups,
    () => {
        const current = Number(props.modelValue) || 0;
        if (current === 0) return;
        if (!props.groups.some((item) => Number(item.id) === current)) {
            emit("update:modelValue", 0);
        }
    },
    { deep: true },
);
</script>
