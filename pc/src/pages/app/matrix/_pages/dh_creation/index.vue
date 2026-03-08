<template>
    <div
        class="h-full flex flex-col bg-[#F3F4F6] rounded-[20px] overflow-hidden border border-br min-w-[1000px]"
        v-if="!isCreate">
        <div class="flex-shrink-0 px-8 py-6 bg-white border-b border-br relative overflow-hidden">
            <div
                class="absolute -top-24 -left-24 w-64 h-64 bg-[#0065fb]/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-2xl bg-primary shadow-[#0065fb]/20 text-white">
                        <Icon name="el-icon-Monitor" :size="24"></Icon>
                    </div>
                    <div>
                        <h1 class="text-[22px] font-[900] text-[#1E293B] tracking-tight leading-none">
                            批量数字人控制台
                        </h1>
                        <div class="flex items-center gap-2 mt-2">
                            <p class="text-[13px] text-[#64748B] font-medium">
                                当前共有 <span class="text-primary mx-0.5">{{ pager.count }}</span> 个创作任务正在运行
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div
                        class="flex items-center rounded-full h-[44px] border border-br px-1 transition-all focus-within:border-[#0065fb] focus-within:bg-white focus-within:">
                        <ElInput
                            v-model="queryParams.name"
                            class="!w-[240px] search-input"
                            clearable
                            prefix-icon="el-icon-Search"
                            placeholder="搜索任务名称..."
                            @clear="resetPage()"
                            @keydown.enter="resetPage()">
                        </ElInput>
                        <ElButton
                            type="primary"
                            class="!rounded-full !h-[36px] !px-5 !text-xs !font-black"
                            @click="resetPage()">
                            搜索
                        </ElButton>
                    </div>

                    <div class="w-[1px] h-6 bg-[#E2E8F0] mx-1"></div>

                    <ElButton
                        type="primary"
                        class="!rounded-full !h-[44px] !px-6 !font-black !text-sm !shadow-xl !shadow-[#0065fb]/20 bg-gradient-to-r from-[#0065fb] to-[#7C3AED] !border-none hover:opacity-90 active:scale-95 transition-all"
                        @click="handleAddTask()">
                        <div class="flex items-center gap-2">
                            <Icon name="el-icon-Plus" :size="16"></Icon>
                            <span>新建创作任务</span>
                        </div>
                    </ElButton>
                </div>
            </div>
        </div>

        <div class="grow min-h-0" v-spin="{ show: loading, text: '加载中...' }">
            <ElScrollbar :distance="20" @end-reached="load">
                <div class="p-6">
                    <template v-if="pager.lists.length > 0">
                        <div class="grid grid-cols-2 gap-4">
                            <div
                                v-for="(item, index) in pager.lists"
                                :key="index"
                                class="group relative flex bg-white rounded-[24px] p-5 border border-[#F3F4F6] transition-all duration-500 hover:shadow-[0_20px_50px_rgba(0,0,0,0.05)]"
                                @click="handleEditTask(item)">
                                <div
                                    class="absolute left-0 top-1/4 bottom-1/4 w-1 rounded-r-full transition-all duration-300 group-hover:top-4 group-hover:bottom-4"
                                    :style="{ backgroundColor: getStatusHex(item.status) }"></div>

                                <button
                                    class="absolute right-4 top-4 w-8 h-8 opacity-0 group-hover:opacity-100"
                                    @click.stop="handleDeleteTask(item.id, index)">
                                    <close-btn :icon-size="16"></close-btn>
                                </button>

                                <div class="flex-shrink-0 w-[160px] h-[210px] relative overflow-hidden rounded-[18px]">
                                    <ElImage
                                        v-if="item.pic"
                                        :src="item.pic"
                                        class="w-full h-full transform transition-transform duration-1000 group-hover:scale-110"
                                        fit="cover">
                                        <template #error><PlaceholderStyle /></template>
                                    </ElImage>
                                    <div v-else class="w-full h-full"><PlaceholderStyle /></div>
                                    <div
                                        class="absolute bottom-0 inset-x-0 h-1/2 bg-gradient-to-t from-black/60 to-transparent"></div>
                                    <div
                                        v-if="item.automatic_clip == 1"
                                        class="absolute bottom-3 left-3 px-2 py-0.5 rounded-lg bg-[#0065fb]/90 text-white text-[10px] backdrop-blur-sm">
                                        AI智能剪辑
                                    </div>
                                </div>

                                <div class="flex-1 ml-6 flex flex-col py-1">
                                    <h3
                                        class="text-lg font-medium text-[#111827] line-clamp-1 mb-2 group-hover:text-primary transition-colors">
                                        {{ item.name }}
                                    </h3>

                                    <div class="flex items-center gap-3 mb-4">
                                        <span
                                            :style="{
                                                color: getStatusHex(item.status),
                                                backgroundColor: getStatusHex(item.status) + '15',
                                            }"
                                            class="px-3 py-1 rounded-xl text-xs font-medium border border-transparent">
                                            {{ statusMap[item.status] }}
                                        </span>
                                        <span class="text-[#9CA3AF] text-xs font-medium italic"
                                            >ID: {{ item.id.toString().slice(-6) }}</span
                                        >
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 mb-4">
                                        <div
                                            class="bg-[#F9FAFB] rounded-2xl p-3 border border-[#F3F4F6] transition-colors group-hover:bg-white group-hover:border-[#EEF2FF]">
                                            <p
                                                class="text-[10px] text-[#9CA3AF] uppercase font-medium tracking-wider mb-1">
                                                成功生成
                                            </p>
                                            <div class="flex items-end gap-1">
                                                <span class="text-xl font-black text-[#10B981] leading-none">{{
                                                    item.success_num || 0
                                                }}</span>
                                                <span class="text-[10px] text-[#6B7280]">/ {{ item.video_count }}</span>
                                            </div>
                                        </div>
                                        <div
                                            class="bg-[#F9FAFB] rounded-2xl p-3 border border-[#F3F4F6] transition-colors group-hover:bg-white group-hover:border-[#FEF2F2]">
                                            <p
                                                class="text-[10px] text-[#9CA3AF] uppercase font-medium tracking-wider mb-1">
                                                异常中断
                                            </p>
                                            <span
                                                class="text-xl font-black"
                                                :class="item.error_num > 0 ? 'text-[#EF4444]' : 'text-[#374151]'">
                                                {{ item.error_num || 0 }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-auto flex items-center justify-between">
                                        <span class="text-[11px] text-[#9CA3AF] font-medium">
                                            {{ item.create_time.split(" ")[0] }}
                                        </span>
                                        <button
                                            @click.stop="handlePreviewVideoResult(item)"
                                            class="px-5 py-2 rounded-xl bg-[#1F2937] text-white text-xs font-medium hover:bg-primary transition-all active:scale-95">
                                            查看成果
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="pager.isLoad" />
                    </template>
                    <div
                        class="h-[500px] flex flex-col items-center justify-center bg-white rounded-[40px] shadow-inner border-2 border-dashed border-[#E5E7EB]"
                        v-else>
                        <Empty
                            btn-text="点击开始创作"
                            msg="让 AI 赋能您的视频生产"
                            :custom-click="() => handleAddTask()" />
                    </div>
                </div>
            </ElScrollbar>
        </div>

        <preview-video-result
            v-if="showPreviewVideoResult"
            ref="previewVideoResultRef"
            @close="showPreviewVideoResult = false" />
    </div>
    <create-panel ref="createPanelRef" v-else @back="back" />
</template>

<script setup lang="ts">
import { getDigitalHumanList, deleteDigitalHuman } from "@/api/matrix";
import Empty from "@/pages/app/matrix/_components/empty.vue";
import { SidebarTypeEnum } from "@/pages/app/matrix/_enums";
import CreatePanel from "./_components/create-panel.vue";
import PreviewVideoResult from "./_components/preview-video-result.vue";

const route = useRoute();

const loading = ref<boolean>(true);

const queryParams = reactive({
    name: "",
    page_no: 1,
    page_size: 20,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getDigitalHumanList,
    params: queryParams,
    isScroll: true,
});

const statusMap = {
    0: "草稿暂存",
    1: "等待队列",
    2: "正在计算",
    3: "渲染完成",
    4: "发生错误",
    5: "部分交付",
};
const PlaceholderStyle = defineComponent({
    render() {
        return h(
            "div",
            { class: "w-full h-full flex flex-col items-center justify-center relative bg-[#F1F5F9] overflow-hidden" },
            [
                // 背景装饰几何体
                h("div", { class: "absolute -right-4 -bottom-4 w-24 h-24 bg-[#E2E8F0] rounded-full opacity-50" }),
                h("div", { class: "absolute -left-2 top-10 w-12 h-12 bg-[#E2E8F0] rotate-45 opacity-30" }),
                // 主图标
                h("i", { class: "el-icon-picture-outline text-4xl text-[#CBD5E1] mb-3 relative z-10" }),
                h(
                    "span",
                    { class: "text-[11px] font-medium text-[#94A3B8] tracking-widest uppercase relative z-10" },
                    "No Preview"
                ),
            ]
        );
    },
});

/**
 * 使用纯 HEX 色值控制状态颜色
 */
const getStatusHex = (status: number) => {
    const hexMap: Record<number, string> = {
        0: "#6B7280", // 灰色
        1: "#3B82F6", // 蓝色
        2: "#F59E0B", // 琥珀色
        3: "#10B981", // 绿色
        4: "#EF4444", // 红色
        5: "#8B5CF6", // 紫色
    };
    return hexMap[status] || "#6B7280";
};

const back = () => {
    isCreate.value = false;
    window.history.replaceState("", "", `?type=${SidebarTypeEnum.DIGITAL_HUMAN_CREATION}`);
    resetPage();
};

const load = async (e: any) => {
    if (e == "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no++;
        await getLists();
    }
};

const createPanelRef = ref<InstanceType<typeof CreatePanel>>();
const isCreate = ref(route.query.is_create == "1");

const handleAddTask = async () => {
    isCreate.value = true;
    replaceState({ is_create: 1 });
    await nextTick();
    createPanelRef.value?.createEmptyTask();
};

const handleEditTask = async (item?: any) => {
    if (item.status != 0) return;
    isCreate.value = true;
    replaceState({ is_create: 1, create_id: item.id });
};

const handleDeleteTask = async (id: string, index: number) => {
    useNuxtApp().$confirm({
        message: "确认删除该任务？此操作不可撤回。",
        onConfirm: async () => {
            try {
                await deleteDigitalHuman({ id });
                pager.lists.splice(index, 1);
            } catch (error: any) {
                feedback.msgError(error);
            }
        },
    });
};

const previewVideoResultRef = ref<InstanceType<typeof PreviewVideoResult>>();
const showPreviewVideoResult = ref(false);
const handlePreviewVideoResult = async (item: any) => {
    showPreviewVideoResult.value = true;
    await nextTick();
    previewVideoResultRef.value?.open({ id: item.id, auto_type: item.auto_type });
};

const init = async () => {
    try {
        await getLists();
    } finally {
        loading.value = false;
    }
};

init();
</script>

<style scoped lang="scss">
/* 搜索框美化 */
:deep(.search-input) {
    .el-input__wrapper {
        background: transparent !important;
        box-shadow: none !important;
        padding-left: 15px;
    }
    .el-input__inner {
        font-weight: 600;
        color: #1e293b;
        &::placeholder {
            color: #94a3b8;
        }
    }
}
</style>
