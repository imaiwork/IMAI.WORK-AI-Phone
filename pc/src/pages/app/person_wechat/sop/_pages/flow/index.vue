<template>
    <div class="h-full flex flex-col bg-white rounded-[20px] border border-br overflow-hidden" v-if="!isCreate">
        <header class="flex items-center justify-between px-[24px] py-[24px] border-b border-br-extra-light">
            <div class="flex items-center gap-[12px]">
                <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_10px_rgba(0,101,251,0.4)]"></div>
                <h3 class="text-xl font-[900] text-[#0F172A] tracking-tight">客户生命周期流程</h3>
                <ElButton
                    type="primary"
                    class="!rounded-xl !h-10 !px-6 !font-black hover:scale-105 transition-transform ml-2"
                    @click="handleAddFlow">
                    <Icon name="el-icon-Plus" />
                    <span class="ml-1">新建流程</span>
                </ElButton>
            </div>

            <div class="flex items-center gap-[12px]">
                <ElInput
                    v-model="queryParams.flow_name"
                    class="custom-input"
                    placeholder="搜索流程方案..."
                    clearable
                    @clear="clearSearch"
                    @keyup.enter="resetPage()">
                    <template #prefix>
                        <Icon name="el-icon-Search" />
                    </template>
                </ElInput>
            </div>
        </header>

        <div class="grow min-h-0 relative bg-page">
            <ElScrollbar v-if="pager.lists.length > 0" @end-reached="load">
                <div class="grid grid-cols-1 gap-[20px] p-[24px]">
                    <div v-for="(item, index) in pager.lists" :key="index" class="flow-card-container group">
                        <div
                            class="flex items-center justify-between px-[24px] py-[16px] bg-white border-b border-br-extra-light">
                            <div class="flex items-center gap-[12px]">
                                <div
                                    class="w-[40px] h-[40px] rounded-[12px] bg-blue-50 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all duration-300">
                                    <Icon name="el-icon-Connection" :size="20" />
                                </div>
                                <div>
                                    <div
                                        class="text-[16px] font-[900] text-tx-primary group-hover:text-primary transition-colors">
                                        {{ item.flow_name }}
                                    </div>
                                    <div class="text-xs text-tx-secondary">
                                        包含 {{ item.key_stages?.length || 0 }} 个关键阶段
                                    </div>
                                </div>
                            </div>
                            <div
                                class="flex gap-[8px] opacity-0 group-hover:opacity-100 transition-all translate-x-[10px] group-hover:translate-x-0">
                                <ElButton
                                    class="!rounded-[8px] !font-medium"
                                    size="small"
                                    @click="handleEditFlow(item.id)"
                                    >编辑</ElButton
                                >
                                <ElButton
                                    type="danger"
                                    plain
                                    class="!rounded-[8px] !font-medium"
                                    size="small"
                                    @click="handleDeleteFlow(item.id)"
                                    >删除</ElButton
                                >
                            </div>
                        </div>

                        <div class="px-[32px] py-[32px] overflow-x-auto no-scrollbar bg-white rounded-b-xl-custom">
                            <div class="flex items-center">
                                <template v-for="(value, vIndex) in item.key_stages" :key="vIndex">
                                    <div class="flex items-center shrink-0">
                                        <div class="flex flex-col items-center gap-[12px] relative">
                                            <div class="node-circle shadow-sm">
                                                <div class="inner-dot"></div>
                                                <span
                                                    class="absolute -top-[8px] -right-[8px] w-[18px] h-[18px] bg-gray-950 text-white text-[10px] rounded-full flex items-center justify-center font-medium">
                                                    {{ vIndex + 1 }}
                                                </span>
                                            </div>
                                            <div class="stage-tag">{{ value.sub_stage_name }}</div>
                                        </div>

                                        <div v-if="vIndex < item.key_stages.length - 1" class="connector-line">
                                            <div class="arrow"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <load-text :is-load="pager.isLoad" />
            </ElScrollbar>

            <div v-else class="h-full flex flex-col items-center justify-center bg-white">
                <ElEmpty description="暂无流程方案，点击上方按钮新建" :image-size="120" />
            </div>
        </div>

        <flow-add-pop v-if="showAdd" ref="flowAddRef" @close="showAdd = false" @success="resetPage" />
    </div>
    <create-panel ref="createPanelRef" v-else @delete="handleDeleteFlow" @back="closeEdit" />
</template>
<script setup lang="ts">
import { sopFlowLists, sopFlowDelete } from "@/api/person_wechat";
import FlowAddPop from "./_components/flow-add.vue";
import CreatePanel from "./_components/create-panel.vue";
import { SidebarTypeEnum } from "../../_enums";

const { query } = useRoute();
const nuxtApp = useNuxtApp();
const queryParams = reactive({
    flow_name: "",
    page_no: 1,
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: sopFlowLists,
    params: queryParams,
    isScroll: true,
});

const clearSearch = () => {
    queryParams.flow_name = "";
    resetPage();
};

const load = (e: string) => {
    if (e === "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no++;
        getLists();
    }
};

const showAdd = ref(false);
const flowAddRef = ref<InstanceType<typeof FlowAddPop>>();
const createPanelRef = ref<InstanceType<typeof CreatePanel>>();

const isCreate = ref(query.is_create == "1" && parseInt(query.type as string) == SidebarTypeEnum.FLOW);

const handleAddFlow = async () => {
    showAdd.value = true;
    await nextTick();
    flowAddRef.value?.open();
};

const handleEditFlow = async (id: number) => {
    isCreate.value = true;
    await nextTick();
    replaceState({
        id,
        is_create: "1",
    });
    createPanelRef.value?.getDetail(id);
};

const handleDeleteFlow = async (id: number, isClose?: boolean) => {
    await nuxtApp.$confirm({
        message: "确定删除该客户流程吗？",
        onConfirm: async () => {
            try {
                await sopFlowDelete({ id });
                const index = pager.lists.findIndex((item) => item.id == id);
                pager.lists.splice(index, 1);
                feedback.msgSuccess("删除成功");
                isClose && closeEdit();
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const closeEdit = () => {
    isCreate.value = false;
    window.history.replaceState("", "", `?type=${SidebarTypeEnum.FLOW}`);
    resetPage();
};

onMounted(() => {
    if (!isCreate.value) {
        getLists();
    }
});
</script>
<style scoped lang="scss">
.flow-card-container {
    @apply bg-white border border-br rounded-xl overflow-hidden transition-all duration-300;

    &:hover {
        @apply border-primary-light-7;
        box-shadow: var(--el-box-shadow-light);
    }
}

.node-circle {
    @apply w-[44px] h-[44px] rounded-full bg-white border-[3px] border-blue-50 flex items-center justify-center relative z-10 transition-all;

    .inner-dot {
        @apply w-[10px] h-[10px] rounded-full bg-primary;
    }

    &:hover {
        @apply border-primary-light-8 scale-110;
    }
}

.stage-tag {
    @apply text-[13px] font-[900] text-tx-primary bg-gray-50 px-[12px] py-[4px] rounded-[8px] border border-br-extra-light;
}

.connector-line {
    @apply w-[60px] h-[2px] bg-gray-100 mx-[4px] relative mb-[32px];

    &::after {
        content: "";
        @apply absolute inset-0 bg-gradient-to-r from-[#0065fb]/40 to-[transparent] scale-x-0 origin-left transition-transform duration-500;
    }

    .arrow {
        @apply absolute right-0 top-1/2 -translate-y-1/2 w-[6px] h-[6px] border-t-2 border-r-2 border-gray-300 rotate-45;
    }
}

.flow-card-container:hover .connector-line {
    @apply bg-blue-100;
    &::after {
        @apply scale-x-100;
    }
    .arrow {
        @apply border-primary-light-5;
    }
}
</style>
