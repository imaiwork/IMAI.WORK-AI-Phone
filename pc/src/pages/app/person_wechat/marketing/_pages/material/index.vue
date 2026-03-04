<template>
    <div class="w-full h-full flex flex-col gap-y-3">
        <div
            v-if="mode === 'page'"
            class="flex-shrink-0 bg-white h-[64px] px-6 rounded-[20px] border border-br flex items-center justify-between">
            <div class="flex items-center gap-x-2">
                <div
                    v-for="item in getTabs"
                    :key="item.type"
                    @click="handleChangeType(item.type)"
                    :class="[
                        'flex items-center gap-x-2.5 px-5 py-2.5 rounded-xl cursor-pointer transition-all duration-300 group',
                        currentCate === item.type
                            ? 'bg-[#0065fb]/10 text-primary shadow-light'
                            : 'text-[#64748B] hover:bg-slate-50 hover:text-tx-primary',
                    ]">
                    <span
                        :class="
                            currentCate === item.type ? 'text-primary' : 'text-[#94A3B8] group-hover:text-[#0065fb]/70'
                        ">
                        <Icon :name="`local-icon-${item.icon}`" :size="20" />
                    </span>

                    <span class="text-[15px] font-black tracking-tight">{{ item.name }}</span>
                </div>
            </div>

            <div class="flex items-center gap-x-3 pr-2">
                <div class="h-4 w-[1px] bg-slate-200"></div>
                <div class="text-xs text-tx-placeholder font-medium">
                    当前限制: <span class="text-primary font-medium">{{ limit }}</span> 项素材
                </div>
            </div>
        </div>

        <div class="grow min-h-0 flex gap-x-3">
            <div class="w-[240px] bg-white rounded-[20px] border border-br flex-shrink-0 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-[#F8FAFC]">
                    <div class="text-xs text-tx-placeholder font-black uppercase tracking-widest">Category</div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                    <Sidebar />
                </div>
            </div>

            <div class="grow min-h-0 bg-white rounded-[20px] border border-br overflow-hidden flex flex-col">
                <div class="p-5 border-b border-[#F8FAFC] flex items-center justify-between bg-[#f8fafc]/50">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                        <span class="text-[14px] font-medium text-tx-primary">素材库清单</span>
                    </div>
                </div>
                <div class="flex-1 overflow-hidden">
                    <Container :mode="mode" :limit="limit" @update:select="handleSelect" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import Sidebar from "./sidebar.vue";
import Container from "./container.vue";
import { useCate, useFile } from "../../_hooks/useMaterial";

const props = withDefaults(
    defineProps<{
        mode?: "page" | "popup";
        type?: MaterialTypeEnum;
        limit?: number;
    }>(),
    {
        mode: "page",
        limit: 9,
    }
);

const emit = defineEmits<{
    (e: "update:select", value: Record<string, any>): void;
}>();

const { currentCate, getCateLists } = useCate();
const { pager, selectItem, queryParams, getLists } = useFile();

// 优化图标名称，使其与你之前的 UI 风格更贴合
const typeTabs = ref<any[]>([
    { name: "图片素材", icon: "image_ai_fill", type: MaterialTypeEnum.IMAGE },
    { name: "视频素材", icon: "video_ai_fill", type: MaterialTypeEnum.VIDEO },
    { name: "链接素材", icon: "link_fill", type: MaterialTypeEnum.LINK },
    { name: "小程序", icon: "mini_program_fill", type: MaterialTypeEnum.MINI_PROGRAM },
    { name: "文件档案", icon: "folder_fill", type: MaterialTypeEnum.FILE },
]);

const getTabs = computed(() => {
    if (!props.type) return typeTabs.value;
    return typeTabs.value.filter((item) => item.type === props.type);
});

const handleChangeType = (type: (typeof MaterialTypeEnum)[keyof typeof MaterialTypeEnum]) => {
    if (currentCate.value === type) return;
    pager.lists = [];
    selectItem.value = [];
    currentCate.value = type;
    queryParams.file_type = type;
    getCateLists();
    getLists();
};

const handleSelect = (value: any[]) => {
    if (value && value.length) {
        if (props.limit != 1) {
            emit("update:select", value);
        } else {
            emit("update:select", value[0]);
        }
    } else {
        emit("update:select", {});
    }
};

watch(
    () => props.type,
    (value) => {
        if (value) handleChangeType(value);
    },
    { immediate: true }
);
</script>

<style scoped lang="scss">
/* 滚动条优化 */
.custom-scrollbar {
    &::-webkit-scrollbar {
        width: 4px;
    }
    &::-webkit-scrollbar-thumb {
        @apply bg-slate-200 rounded-full hover:bg-slate-300;
    }
}

:deep(.material-sidebar) {
    border: none !important;
}
</style>
