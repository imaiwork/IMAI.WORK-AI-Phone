<template>
    <div class="h-full flex flex-col bg-app-bg-2 rounded-[20px]" v-if="!isCreate">
        <div class="flex-shrink-0 px-[14px] border-[0] border-b-[1px] border-app-border-1">
            <ElScrollbar>
                <div class="flex items-center justify-end h-[88px]">
                    <div class="flex items-center gap-[14px]">
                        <ElInput
                            v-model="queryParams.name"
                            prefix-icon="el-icon-Search"
                            class="!w-[240px] search-name-input"
                            placeholder="请输入任务名称"
                            clearable
                            @clear="resetPage()"
                            @keydown.enter="resetPage()">
                            <template #append>
                                <ElButton text @click="resetPage()"> 搜索 </ElButton>
                            </template>
                        </ElInput>
                        <ElButton type="primary" class="!rounded-full !h-10 !px-4" @click="handleAddTask()">
                            <Icon name="local-icon-add_circle" color="#ffffff"></Icon>
                            <span class="ml-2">创建批量数字人任务</span>
                        </ElButton>
                        <div>
                            <ElTooltip content="刷新">
                                <ElButton
                                    circle
                                    color="#1f1f1f"
                                    icon="el-icon-Refresh"
                                    class="!w-10 !h-10"
                                    @click="resetPage()"></ElButton>
                            </ElTooltip>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div
            class="grow min-h-0 overflow-y-auto flex flex-col dynamic-scroller"
            :infinite-scroll-immediate="false"
            :infinite-scroll-disabled="!pager.isLoad"
            :infinite-scroll-distance="10"
            v-infinite-scroll="load">
            <div class="h-full p-4" v-loading="pager.loading">
                <div v-if="pager.lists.length">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        <div
                            v-for="(item, index) in pager.lists"
                            class="flex gap-x-4 h-[222px] relative overflow-hidden bg-app-bg-3 border border-app-border-2 rounded-xl p-4"
                            :class="{ 'cursor-pointer': item.status == 0 }"
                            :key="index"
                            @click="handleEditTask(item)">
                            <div class="absolute right-2 top-2 w-6 h-6" @click.stop="handleDeleteTask(item.id, index)">
                                <close-btn :theme="ThemeEnum.DARK"></close-btn>
                            </div>
                            <div
                                class="flex-shrink-0 w-[143px] border border-app-border-2 overflow-hidden rounded-md bg-black">
                                <ElImage :src="item.pic" class="w-full h-full" fit="cover">
                                    <template #error>
                                        <div
                                            class="w-full h-full flex items-center justify-center text-white card-gradient">
                                            暂无封面
                                        </div>
                                    </template>
                                </ElImage>
                            </div>
                            <div class="flex-1 flex flex-col gap-y-[10px]">
                                <div class="text-white break-all line-clamp-1 mr-4">
                                    {{ item.name }}<template v-if="item.automatic_clip == 1">（AI剪辑）</template>
                                </div>
                                <div class="flex items-center gap-x-1">
                                    <span class="text-white"
                                        >生成状态：{{ item.success_num || 0 }}/{{ item.video_count || 1 }}</span
                                    >
                                    <span :class="[[3, 5].includes(item.status) ? 'text-[#3BB840]' : 'text-primary']">{{
                                        statusMap[item.status]
                                    }}</span>
                                </div>
                                <div class="flex items-center gap-x-1 text-white">
                                    <span>生成视频：</span>
                                    <span>成功{{ item.success_num || 0 }}</span>
                                    <span>失败{{ item.error_num || 0 }}</span>
                                </div>
                                <div class="text-white">任务创建：{{ item.create_time }}</div>
                                <div class="text-white">最新提交：{{ item.latest_submission_time }}</div>
                                <div class="flex items-center gap-x-2">
                                    <ElButton
                                        class="!h-10 w-[126px] !border-app-border-2"
                                        color="#1f1f1f"
                                        @click.stop="handlePreviewVideoResult(item.id)"
                                        >查看视频</ElButton
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!pager.isLoad" class="text-white text-center text-xs w-full py-4">暂无更多了~</div>
                </div>

                <div class="h-full flex items-center justify-center" v-else>
                    <Empty
                        btn-text="创建批量数字人任务"
                        msg="快去发布你的专属数字人吧"
                        :custom-click="() => handleAddTask()" />
                </div>
            </div>
        </div>
        <preview-video-result
            v-if="showPreviewVideoResult"
            ref="previewVideoResultRef"
            @close="showPreviewVideoResult = false" />
    </div>
    <create-panel ref="createPanelRef" v-else @back="back" />
</template>

<script setup lang="ts">
import { getDigitalHumanList, deleteDigitalHuman } from "@/api/redbook";
import Empty from "@/pages/app/matrix/_components/empty.vue";
import { ThemeEnum } from "@/enums/appEnums";
import { SidebarTypeEnum } from "@/pages/app/matrix/_enums";
import CreatePanel from "./_components/create-panel.vue";
import PreviewVideoResult from "./_components/preview-video-result.vue";
const route = useRoute();

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
    0: "草稿箱",
    1: "待处理",
    2: "生成中",
    3: "已完成",
    4: "失败",
    5: "部分完成",
};

const back = () => {
    isCreate.value = false;
    window.history.replaceState("", "", `?type=${SidebarTypeEnum.DIGITAL_HUMAN_CREATION}`);
    resetPage();
};

const load = () => {
    queryParams.page_no++;
    getLists();
};

const createPanelRef = ref<InstanceType<typeof CreatePanel>>();
const isCreate = ref(route.query.is_create == "1");

const handleAddTask = async () => {
    isCreate.value = true;
    replaceState({
        is_create: 1,
    });
    await nextTick();
    createPanelRef.value?.createEmptyTask();
};

const handleEditTask = async (item?: any) => {
    if (item.status != 0) return;
    isCreate.value = true;
    replaceState({
        is_create: 1,
        create_id: item.id,
    });
};

const handleDeleteTask = async (id: string, index: number) => {
    useNuxtApp().$confirm({
        message: "确定要删除该数字人任务吗？",
        theme: "dark",
        onConfirm: async () => {
            try {
                await deleteDigitalHuman({ id });
                pager.lists.splice(index, 1);
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const previewVideoResultRef = ref<InstanceType<typeof PreviewVideoResult>>();
const showPreviewVideoResult = ref(false);
const handlePreviewVideoResult = async (id: string) => {
    showPreviewVideoResult.value = true;
    await nextTick();
    previewVideoResultRef.value?.open(id);
};

getLists();
</script>

<style scoped></style>
