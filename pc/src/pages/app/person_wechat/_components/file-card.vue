<template>
    <div class="file-card-item">
        <div class="file-icon-wrapper">
            <Icon
                name="local-icon-file_fill"
                :color="iconColor"
                :size="iconSize"
                class="transition-transform group-hover:scale-110" />
            <div class="file-ext-tag" :style="{ backgroundColor: iconColor }">
                {{ fileExt }}
            </div>
        </div>

        <div class="file-info">
            <div class="file-name" :title="name">
                {{ name }}
            </div>
            <div class="file-meta">
                <span>{{ fileExt.toUpperCase() }} 文档</span>
                <span class="divider"></span>
                <span>点击预览详情</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
    defineProps<{
        name: string;
        url: string;
        iconSize?: number;
    }>(),
    {
        iconSize: 34,
    }
);

// 根据文件后缀动态计算颜色，增加辨识度
const fileExt = computed(() => {
    return props.name?.split(".").pop()?.toLowerCase() || "file";
});

const iconColor = computed(() => {
    const colorMap: Record<string, string> = {
        pdf: "#F5222D", // PDF 红色
        doc: "#1890FF", // Word 蓝色
        docx: "#1890FF",
        xls: "#52C41A", // Excel 绿色
        xlsx: "#52C41A",
        ppt: "#FA8C16", // PPT 橙色
        pptx: "#FA8C16",
        zip: "#722ED1", // 压缩包 紫色
        rar: "#722ED1",
    };
    return colorMap[fileExt.value] || "#80B8F8"; // 默认使用你之前的蓝色
});
</script>

<style scoped lang="scss">
.file-card-item {
    @apply h-full w-full bg-white rounded-xl p-3 flex items-center gap-4 transition-all duration-300;
    border: 1px solid #f2f2f2;

    &:hover {
        @apply border-primary-light-8 bg-[#eff6ff]/20 shadow-light;
    }
}

.file-icon-wrapper {
    @apply relative flex-shrink-0 w-[54px] h-[54px] bg-gray-50 rounded-lg flex items-center justify-center;
}

.file-ext-tag {
    @apply absolute -bottom-1 -right-1 px-1.5 py-0.5 rounded-md text-[9px] text-white font-[900] scale-90 shadow-light;
    text-transform: uppercase;
}

.file-info {
    @apply flex-1 min-w-0 flex flex-col justify-center gap-1;
}

.file-name {
    @apply text-[14px] font-[900] text-tx-primary line-clamp-1 break-all;
}

.file-meta {
    @apply flex items-center text-[11px] text-tx-placeholder font-medium;

    .divider {
        @apply w-[1px] h-[10px] bg-gray-200 mx-2;
    }
}
</style>
