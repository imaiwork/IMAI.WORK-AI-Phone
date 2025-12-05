<template>
    <div class="h-full flex flex-col bg-white rounded-xl" v-if="!isCreate">
        <div class="flex items-center justify-between p-4">
            <ElButton type="primary" @click="handleAddFlow">新建客户流程</ElButton>
            <div class="flex items-center gap-2">
                <ElInput
                    v-model="queryParams.flow_name"
                    class="!w-[240px]"
                    clearable
                    placeholder="请输入流程名称"
                    @clear="clearSearch"
                    @keyup.enter="resetPage()">
                    <template #append>
                        <ElButton @click="resetPage()">
                            <Icon name="el-icon-Search"></Icon>
                        </ElButton>
                    </template>
                </ElInput>
            </div>
        </div>
        <div class="grow min-h-0 flex flex-col" v-loading="pager.loading">
            <div class="grow min-h-0 py-2" v-if="pager.lists.length > 0">
                <ElScrollbar>
                    <div class="flex flex-col gap-6 p-4">
                        <div
                            v-for="(item, index) in pager.lists"
                            :key="index"
                            class="h-[160px] flex flex-col rounded-xl shadow-lighter px-4">
                            <div class="flex items-center justify-between h-[56px]">
                                <div class="flex items-center gap-4">
                                    <img src="../../../_assets/images/flow_title_img.png" class="w-6 h-6" />
                                    <div class="text-lg font-bold">
                                        {{ item.flow_name }}
                                    </div>
                                </div>
                                <div>
                                    <ElButton type="primary" size="small" @click="handleEditFlow(item.id)"
                                        >编辑</ElButton
                                    >
                                    <ElButton type="danger" size="small" @click="handleDeleteFlow(item.id)"
                                        >删除</ElButton
                                    >
                                </div>
                            </div>
                            <ElDivider class="!m-0 border-[#E7E7E7]" />
                            <div class="grow min-h-0">
                                <div class="h-full flex items-center whitespace-nowrap overflow-x-auto px-6">
                                    <div
                                        v-for="(value, vIndex) in item.key_stages"
                                        :key="vIndex"
                                        class="flex items-center gap-2 w-[200px] flex-shrink-0">
                                        <div class="flex flex-col items-center gap-2">
                                            <Icon
                                                name="el-icon-LocationFilled"
                                                color="var(--color-primary)"
                                                :size="20"></Icon>
                                            <span class="text-[#5E656E]">{{ value.sub_stage_name }}</span>
                                        </div>
                                        <div
                                            class="flex-1 flex justify-center"
                                            v-if="vIndex < item.key_stages.length - 1">
                                            <img src="../../../_assets/images/flow_arrow_right.png" class="h-6" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
                <div class="flex justify-end p-4">
                    <pagination v-model="pager" @change="getLists"></pagination>
                </div>
            </div>
            <div v-else class="h-full flex items-center justify-center">
                <ElEmpty description="暂无数据"></ElEmpty>
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

<style scoped lang="scss"></style>
