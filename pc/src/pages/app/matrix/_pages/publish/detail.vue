<template>
    <ElDrawer v-model="show" body-class="!p-0" size="480px" :with-header="false" class="custom-light-drawer">
        <div class="h-full flex flex-col bg-slate-50" ref="containerRef">
            <div class="absolute w-8 h-8 top-3 right-3 z-50" @click="close">
                <close-btn :icon-size="12" />
            </div>

            <div class="bg-white px-8 pt-10 pb-8 shadow-[0_1px_3px_0_rgba(0,0,0,0.02)] border-b border-[#F1F5F9]">
                <div class="flex items-center gap-2 mb-4">
                    <span class="px-2 py-0.5 rounded-lg bg-[#EEF2FF] text-[10px] font-black tracking-wider text-primary"
                        >任务档案</span
                    >
                    <div class="h-[1px] flex-1 bg-[#F1F5F9]"></div>
                </div>

                <h2 class="text-[22px] font-black text-[#1E293B] tracking-tight mb-6">
                    {{ detail?.name || "未命名任务" }}
                </h2>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-[#F1F5F9]">
                        <p class="text-[10px] text-[#94A3B8] font-medium uppercase mb-1">任务类型</p>
                        <p class="text-[13px] font-medium text-[#334155]">{{ detail?.task_category }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-[#F1F5F9]">
                        <p class="text-[10px] text-[#94A3B8] font-medium uppercase mb-1">每日发布量</p>
                        <p class="text-[13px] font-medium text-[#1E293B]">
                            {{ detail?.count || 0 }} <span class="text-[10px] font-normal text-[#94A3B8]">条 / 天</span>
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-4 p-4 rounded-2xl bg-white border border-br">
                    <div class="w-12 h-12 rounded-xl bg-[#F1F5F9] flex items-center justify-center p-2">
                        <img v-if="getPlatformIcon" :src="getPlatformIcon" class="w-full h-full object-contain" />
                        <Icon v-else name="el-icon-User" :size="20" color="#0065fb"></Icon>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black text-[#1E293B] truncate">{{ detail?.account_name }}</p>
                        <p class="text-[11px] text-[#64748B] mt-0.5">
                            关联设备：<span class="text-primary font-medium">{{ detail?.device_name }}</span>
                        </p>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <span class="text-[10px] text-[#94A3B8] font-medium uppercase">创建于</span>
                        <span class="text-[10px] text-[#64748B] font-medium">{{
                            detail?.create_time?.split(" ")[0] || "-"
                        }}</span>
                    </div>
                </div>
            </div>

            <div class="px-8 py-5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-4 bg-primary rounded-full"></span>
                    <span class="text-xs font-black tracking-widest text-[#64748B] uppercase">发布动态记录</span>
                </div>
                <div class="text-[10px] px-2.5 py-1 rounded-full bg-[#F1F5F9] text-[#64748B] font-medium">
                    共 {{ dataLists.length }} 条
                </div>
            </div>

            <div class="grow min-h-0">
                <ElScrollbar v-if="dataLists.length > 0">
                    <div class="px-6 pb-10 space-y-5">
                        <div
                            v-for="(item, index) in dataLists"
                            :key="index"
                            class="bg-white border border-br rounded-[24px] overflow-hidden transition-all hover:border-[#0065fb]/20 group">
                            <div class="p-5">
                                <div class="flex justify-between items-center mb-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-black text-[#94A3B8] uppercase"
                                            >#{{ String(index + 1).padStart(2, "0") }}</span
                                        >
                                        <span class="w-1 h-1 rounded-full bg-[#CBD5E1]"></span>
                                        <span class="text-[10px] font-medium text-[#64748B]">{{
                                            item.material_type == 2 ? "图文" : "视频"
                                        }}</span>
                                    </div>
                                    <div
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-black border"
                                        :class="{
                                            'bg-white text-[#94A3B8] border-br': item.status == 0,
                                            'bg-[#ECFDF5] text-[#10B981] border-[#D1FAE5]': item.status == 1,
                                            'bg-[#FEF2F2] text-[#EF4444] border-[#FEE2E2]': item.status == 2,
                                            'bg-[#EEF2FF] text-primary border-[#E0E7FF]': item.status == 3,
                                        }">
                                        {{ ["未发布", "已发布", "发布失败", "发布中"][item.status] }}
                                    </div>
                                </div>

                                <div class="mb-4 rounded-xl overflow-hidden bg-slate-50 border border-[#F1F5F9]">
                                    <div v-if="item.material_type == 2" class="grid grid-cols-4 gap-1.5 p-1.5">
                                        <ElImage
                                            v-for="(img, i) in item.material_url.split(',')"
                                            :key="i"
                                            class="aspect-square w-full rounded-lg border border-white"
                                            fit="cover"
                                            :src="img"
                                            :preview-src-list="item.material_url.split(',')"
                                            preview-teleported />
                                    </div>
                                    <div v-if="item.material_type == 1" class="relative group cursor-pointer">
                                        <ElImage
                                            v-if="item.pic"
                                            :src="item.pic"
                                            class="w-full aspect-video object-cover">
                                            <template #error>
                                                <div
                                                    class="flex flex-col items-center justify-center gap-2 w-full h-full bg-slate-50 text-[#CBD5E1]">
                                                    <Icon name="el-icon-Picture" :size="32"></Icon>
                                                    <span class="text-[10px] font-medium opacity-60">图片加载失败</span>
                                                </div>
                                            </template>
                                        </ElImage>
                                        <video
                                            v-else
                                            ref="videoRef"
                                            :src="item.material_url"
                                            class="w-full aspect-video object-cover" />
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-[#000000]/0 group-hover:bg-[#000000]/10 transition-all"
                                            @click="handlePlay(item.material_url)">
                                            <div
                                                class="w-12 h-12 rounded-full bg-[#ffffff]/90 shadow-light flex items-center justify-center text-primary opacity-0 group-hover:opacity-100 transition-all">
                                                <Icon name="el-icon-VideoPlay" :size="20"></Icon>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <h4 class="text-sm font-medium text-[#1E293B] line-clamp-1">
                                        {{ item.material_title }}
                                    </h4>
                                    <p class="text-xs text-[#64748B] leading-relaxed line-clamp-2">
                                        {{ item.material_subtitle }}
                                    </p>

                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="topic in item.material_tag"
                                            :key="topic"
                                            class="text-[10px] font-medium text-primary bg-[#EEF2FF] px-2 py-0.5 rounded"
                                            >#{{ topic }}</span
                                        >
                                    </div>

                                    <div class="flex items-center justify-between pt-4 mt-2 border-t border-[#F1F5F9]">
                                        <div class="flex flex-col">
                                            <span class="text-[9px] text-[#94A3B8] font-medium uppercase tracking-wider"
                                                >发布时间</span
                                            >
                                            <span class="text-[11px] text-[#334155] font-medium">{{
                                                item.publish_time || "待发布"
                                            }}</span>
                                        </div>
                                        <button
                                            @click="handleDelete(item.id)"
                                            class="w-9 h-9 rounded-xl bg-slate-50 text-[#94A3B8] hover:bg-[#FEF2F2] hover:border-[transparent] hover:text-[#EF4444] border border-[#F1F5F9] transition-all flex items-center justify-center">
                                            <Icon name="el-icon-Delete" :size="14"></Icon>
                                        </button>
                                    </div>

                                    <div
                                        v-if="item.status == 2"
                                        class="mt-3 p-3 rounded-xl bg-[#FEF2F2] border border-[#FEE2E2] flex items-start gap-2">
                                        <Icon name="el-icon-Warning" :size="14" color="#EF4444" class="mt-0.5"></Icon>
                                        <span class="text-[11px] text-[#EF4444] font-medium"
                                            >失败原因：{{ item.remark }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
                <div class="h-full flex flex-col items-center justify-center" v-else>
                    <ElEmpty :image-size="100" description="暂无发布动态记录" />
                </div>
            </div>
        </div>
    </ElDrawer>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false"></preview-video>
</template>
<script setup lang="ts">
import { getDeviceAccountTaskDetail, getDeviceTaskRecordList, deleteDeviceTaskRecord } from "@/api/device";

const emit = defineEmits<{
    (e: "close"): void;
}>();

const { getPlatform } = useSocialPlatform();

const containerRef = shallowRef();
const show = ref(false);
const showPreviewVideo = ref(false);
const previewVideoRef = shallowRef();
const currRow = ref<any>({});

const detail = ref<any>({});
const dataLists = ref([]);

const getPlatformIcon = computed(() => getPlatform(detail.value?.account_type)?.icon);

const handlePlay = async (url: string) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value.open();
    previewVideoRef.value.setUrl(url);
};

const handleDelete = (id: number) => {
    useNuxtApp().$confirm({
        message: "确定要删除吗？",
        onConfirm: async () => {
            try {
                feedback.loading("删除中...", containerRef.value);
                await deleteDeviceTaskRecord({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            } finally {
                feedback.closeLoading();
            }
        },
    });
};

const open = (row: any) => {
    show.value = true;
    currRow.value = row;
    getDetail(row.id);

    getLists();
};

const close = () => {
    show.value = false;
    emit("close");
};

const getLists = async () => {
    const { lists } = await getDeviceTaskRecordList({
        page_size: 9999,
        task_type: 3,
        publish_id: currRow.value?.publish_id,
        account: currRow.value?.account,
    });
    dataLists.value = lists;
};

const getDetail = async (id: number) => {
    const data = await getDeviceAccountTaskDetail({ id });
    detail.value = data;
};

defineExpose({
    open,
    getDetail,
});
</script>

<style scoped lang="scss">
:deep(.custom-light-drawer) {
    .el-drawer__body {
        background-color: #f8fafc;
    }
}

/* 视频控件隐藏 */
video::-webkit-media-controls {
    display: none !important;
}

/* 滚动条美化 */
:deep(.el-scrollbar__thumb) {
    background-color: #e2e8f0 !important;
    &:hover {
        background-color: #cbd5e1 !important;
    }
}

/* 按钮点击动效 */
button:active {
    transform: scale(0.95);
}
</style>
