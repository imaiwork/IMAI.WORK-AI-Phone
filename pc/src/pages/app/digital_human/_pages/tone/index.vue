<template>
    <div class="h-full flex flex-col bg-app-bg-2 rounded-[20px]">
        <div class="flex-shrink-0 px-[14px]">
            <ElScrollbar>
                <div class="flex items-center justify-end h-[88px]">
                    <div class="flex items-center gap-[14px]">
                        <ElInput
                            v-model="queryParams.name"
                            prefix-icon="el-icon-Search"
                            class="!w-[240px] search-name-input"
                            placeholder="请输入音色名称"
                            clearable
                            @clear="getLists()"
                            @keydown.enter="getLists()">
                            <template #append>
                                <ElButton text @click="getLists()"> 搜索 </ElButton>
                            </template>
                        </ElInput>
                        <ElButton type="primary" class="!rounded-full !h-10 !w-[116px]" @click="handleAdd">
                            <Icon name="local-icon-add_circle" color="#ffffff"></Icon>
                            <span class="ml-2">新增音色</span>
                        </ElButton>
                        <div>
                            <ElTooltip content="刷新">
                                <ElButton
                                    circle
                                    color="#1f1f1f"
                                    icon="el-icon-Refresh"
                                    class="!w-10 !h-10"
                                    @click="getLists()"></ElButton>
                            </ElTooltip>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div class="grow min-h-0 overflow-hidden flex flex-col">
            <div class="grow min-h-0">
                <ElTable
                    height="100%"
                    :data="pager.lists"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '50px' }"
                    v-loading="pager.loading">
                    <ElTableColumn prop="name" label="音色名称" min-width="200"></ElTableColumn>
                    <ElTableColumn label="创建时间" prop="create_time" min-width="200"></ElTableColumn>
                    <ElTableColumn label="使用模型" min-width="120">
                        <template #default="{ row }">
                            {{ getModelType(row.model_version) }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="状态" min-width="120">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2 justify-center">
                                <template v-if="[0, 3, 4, 5].includes(row.status)">
                                    <Icon name="local-icon-clone" color="#ffffff" :size="16"></Icon>
                                    <span class="text-warning">克隆中...</span>
                                </template>
                                <template v-if="row.status === 1">
                                    <Icon
                                        name="local-icon-success_fill"
                                        color="var(--el-color-success)"
                                        :size="16"></Icon>
                                    <span class="text-success">成功</span>
                                </template>
                                <template v-if="row.status === 2">
                                    <Icon name="local-icon-error_fill" :size="16"></Icon>
                                    <span class="text-danger">失败</span>
                                </template>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="操作" width="100" fixed="right">
                        <template #default="{ row }">
                            <ElButton link type="danger" @click="handleDelete(row.id)"> 删除 </ElButton>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <div class="leading-6">
                            <Empty btn-text="新增音色" msg="快去创建你的专属音色吧" :custom-click="handleAdd" />
                        </div>
                    </template>
                </ElTable>
            </div>
            <div class="flex justify-center p-4">
                <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
            </div>
        </div>
        <add-pop v-if="showAddPopup" ref="addPopRef" @close="showAddPopup = false" @success="getLists()"></add-pop>
    </div>
</template>

<script setup lang="ts">
import { getVoiceList, deleteVoice } from "@/api/digital_human";
import { useAppStore } from "@/stores/app";
import Empty from "@/pages/app/digital_human/_components/empty.vue";
import { ToneTypeEnum, DigitalHumanModelVersionEnum } from "@/pages/app/digital_human/_enums";
import AddPop from "./_components/add-pop.vue";

const appStore = useAppStore();

const modelChannel = computed(() => {
    const { channel } = appStore.getDigitalHumanConfig;
    if (channel && channel.length > 0) {
        return channel.filter((item) => item.status == 1 && DigitalHumanModelVersionEnum.CHANJING == item.id);
    }
    return [];
});

const showAddPopup = ref<boolean>(false);
const addPopRef = shallowRef<InstanceType<typeof AddPop>>();

const queryParams = reactive({
    name: "",
    model_version: "",
    builtin: ToneTypeEnum.USER,
});

const { pager, getLists } = usePaging({
    fetchFun: getVoiceList,
    params: queryParams,
});

const getModelType = (type: number) => {
    const data = modelChannel.value.find((item) => item.id == type);
    return data?.name || "";
};

const handleAdd = async () => {
    showAddPopup.value = true;
    await nextTick();
    addPopRef.value?.open();
};

const handleDelete = async (id: number) => {
    useNuxtApp().$confirm({
        title: "提示",
        message: "是否删除该音色",
        theme: "dark",
        onConfirm: async () => {
            try {
                await deleteVoice({ id });
                getLists();
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

onMounted(() => {
    getLists();
});
</script>
