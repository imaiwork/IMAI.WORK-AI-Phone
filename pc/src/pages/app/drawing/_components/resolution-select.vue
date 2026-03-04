<template>
    <div class="flex flex-col gap-3">
        <div class="text-xs font-black text-[#94A3B8] uppercase tracking-widest ml-1">分辨率</div>
        <div class="flex flex-col gap-4 w-full">
            <div class="grid grid-cols-4 gap-3">
                <div
                    v-for="(item, index) in getResolutionOptions"
                    :key="index"
                    @click="currResolution = item.value"
                    class="resolution-card group"
                    :class="{ 'is-active': currResolution === item.value }">
                    <div class="ratio-preview-container">
                        <div class="ratio-shape" :style="getShapeStyle(item.value)"></div>
                    </div>

                    <span class="ratio-label">{{ item.label }}</span>

                    <div class="active-dot" v-if="currResolution === item.value"></div>
                </div>
            </div>

            <div class="flex items-center justify-between px-4 py-3 bg-slate-50 rounded-2xl border border-[#F1F5F9]">
                <div class="flex flex-col">
                    <span class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">Dimension</span>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[15px] font-[900] text-[#1E293B]">{{ getResolutionSize.width }}</span>
                        <span class="text-xs text-[#CBD5E1] font-black">×</span>
                        <span class="text-[15px] font-[900] text-[#1E293B]">{{ getResolutionSize.height }}</span>
                        <span class="text-[11px] text-[#94A3B8] ml-1 font-medium">px</span>
                    </div>
                </div>
                <div class="w-8 h-8 rounded-lg bg-white border border-[#F1F5F9] flex items-center justify-center">
                    <Icon name="el-icon-Crop" :size="16" color="var(--color-primary)"></Icon>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { resolutionOptions, videoResolutionOptions, ModelEnum, seedreamResolutionOptions } from "../_enums";

const props = withDefaults(
    defineProps<{
        type?: "image" | "video";
        model?: ModelEnum;
    }>(),
    {
        type: "image",
    }
);

const emit = defineEmits<{
    (event: "update:resolution", value: { width: number | string; height: number | string; label: string }): void;
}>();

const getResolutionOptions = computed(() => {
    if (props.type === "image") {
        if (props.model == ModelEnum.SEEDREAM) {
            return seedreamResolutionOptions;
        }
        return resolutionOptions;
    }
    return videoResolutionOptions;
});

const currResolution = ref(getResolutionOptions.value[0].value);

// 计算当前宽高并通知父组件
const getResolutionSize = computed(() => {
    const [width, height] = currResolution.value.split("*");
    const label = getResolutionOptions.value.find((item) => item.value === currResolution.value)?.label || "";

    emit("update:resolution", {
        width: width,
        height: height,
        label: label,
    });

    return { width, height };
});

// 根据比例字符串 (如 "1024*1024") 计算预览方块的样式
const getShapeStyle = (value: string) => {
    const [w, h] = value.split("*").map(Number);
    const max = Math.max(w, h);
    // 基础尺寸设为 24px，按比例缩放
    return {
        width: `${(w / max) * 24}px`,
        height: `${(h / max) * 24}px`,
    };
};

watch(
    () => props.model,
    () => {
        currResolution.value = getResolutionOptions.value[0].value;
    }
);
</script>

<style scoped lang="scss">
/* 比例选择卡片 */
.resolution-card {
    @apply relative flex flex-col items-center justify-center p-3 rounded-[20px] 
           bg-white border-2 border-[#F1F5F9] cursor-pointer transition-all duration-300;

    &:hover {
        @apply border-[#0065fb]/30 bg-slate-50 -translate-y-0.5 shadow-light;
    }

    &.is-active {
        @apply border-[#0065fb] bg-[#F5F7FF] shadow-light shadow-[#0065fb]/10;

        .ratio-shape {
            @apply border-[#0065fb] bg-[#0065fb]/20 shadow-[0_0_8px_rgba(0,101,251,0.3)];
        }

        .ratio-label {
            @apply text-primary;
        }
    }
}

/* 比例形状预览容器 */
.ratio-preview-container {
    @apply h-8 flex items-center justify-center mb-2;
}

.ratio-shape {
    @apply border-2 border-[#CBD5E1] rounded-[4px] transition-all duration-500 bg-slate-50;
}

/* 比例文字 */
.ratio-label {
    @apply text-[11px] font-[900] text-[#64748B] transition-colors;
}

/* 选中原点 */
.active-dot {
    @apply absolute top-2 right-2 w-1.5 h-1.5 bg-primary rounded-full;
}
</style>
