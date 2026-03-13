<template>
    <div class="h-full flex flex-col bg-white rounded-[32px] overflow-hidden border border-br min-w-[1000px]">
        <div class="flex-shrink-0 px-8">
            <div class="flex items-center justify-between h-[80px]">
                <div class="flex items-center gap-x-4">
                    <div class="w-12 h-12 flex items-center justify-center rounded-[16px] bg-[#0065FB]/5 text-primary">
                        <Icon name="el-icon-Mic" :size="24"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-[900] tracking-tight">音色库管理</div>
                        <div class="text-[11px] text-[#94A3B8] font-black uppercase tracking-[0.1em]">
                            Voice Matrix Assets
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <ElInput
                        v-model="queryParams.name"
                        class="!w-[200px] custom-input"
                        clearable
                        placeholder="输入音色名搜索..."
                        @clear="getLists()"
                        @keydown.enter="getLists()">
                        <template #prefix>
                            <Icon name="el-icon-Search" />
                        </template>
                    </ElInput>
                    <div class="w-[1px] h-6 bg-[#E2E8F0] mx-1"></div>
                    <ElButton
                        type="primary"
                        @click="handleAdd"
                        class="!rounded-xl !h-[44px] px-6 !font-medium transition-all hover:scale-105 active:scale-95">
                        <Icon name="local-icon-add_circle" :size="18"></Icon>
                        <span class="ml-2">新增音色</span>
                    </ElButton>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 flex flex-col">
            <div class="grow min-h-0">
                <ElTable height="100%" :data="pager.lists" v-loading="pager.loading">
                    <ElTableColumn label="音色识别" min-width="220">
                        <template #default="{ row }">
                            <span class="text-[14px] font-[900] text-[#1E293B]">{{ row.name }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="使用模型" min-width="140">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center">
                                <span
                                    class="px-2 py-1 bg-[#F1F5F9] text-[#64748B] text-[11px] font-black rounded-md border border-[#E2E8F0]">
                                    {{ getModelType(row.model_version) }}
                                </span>
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="状态" min-width="140">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-2">
                                <div
                                    v-if="[0, 3, 4, 5].includes(row.status)"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#fff7ed] text-[#f97316]">
                                    <Icon name="el-icon-Loading" color="#F59E0B" />
                                    <span class="text-xs font-black">训练中</span>
                                </div>
                                <div
                                    v-if="row.status === 1"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#f0fdf4] text-[#16a34a]">
                                    <Icon name="el-icon-CircleCheckFilled" color="#16a34a" />
                                    <span class="text-xs font-black">已生成</span>
                                </div>
                                <div
                                    v-if="row.status === 2"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#fef2f2] text-[#ef4444]">
                                    <Icon name="el-icon-CircleCloseFilled" color="#EF4444" />
                                    <span class="text-xs font-black">失败</span>
                                </div>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="备注" min-width="180">
                        <template #default="{ row }">
                            <em class="text-[13px] font-medium text-[#94A3B8]">{{ row.remark }}</em>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="创建时间" min-width="180">
                        <template #default="{ row }">
                            <span class="text-[13px] font-medium text-[#94A3B8]">{{ row.create_time }}</span>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="管理" width="100" fixed="right" align="right">
                        <template #default="{ row }">
                            <div class="flex items-center justify-end">
                                <ElButton type="danger" link class="!font-medium" @click="handleDelete(row.id)">
                                    删除
                                </ElButton>
                            </div>
                        </template>
                    </ElTableColumn>

                    <template #empty>
                        <div class="py-20">
                            <Empty btn-text="新增音色" msg="克隆您的声音，开启数字人交互" :custom-click="handleAdd" />
                        </div>
                    </template>
                </ElTable>
            </div>

            <div class="shrink-0 h-[72px] px-8 flex items-center justify-between">
                <div class="text-xs font-medium text-[#CBD5E1]">共计 {{ pager.count }} 个音色资源</div>
                <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
            </div>
        </div>
        <add-pop v-if="showAddPopup" ref="addPopRef" @close="showAddPopup = false" @success="getLists()"></add-pop>
    </div>
</template>
<script setup lang="ts">
import { getVoiceList, deleteVoice } from "@/api/digital_human";
import { useAppStore } from "@/stores/app";
import { ToneTypeEnum, DigitalHumanModelVersionEnum } from "@/pages/app/digital_human/_enums";
import Empty from "@/pages/app/digital_human/_components/empty.vue";
import AddPop from "./_components/add-pop.vue";

const appStore = useAppStore();

const modelChannel = computed(() => {
    const { channel } = appStore.getDigitalHumanConfig;
    if (channel && channel.length > 0) {
        return channel.filter(
            (item) =>
                item.status == 1 &&
                [
                    DigitalHumanModelVersionEnum.CHANJING,
                    DigitalHumanModelVersionEnum.STANDARD,
                    DigitalHumanModelVersionEnum.SHANJIAN,
                ].includes(parseInt(item.id))
        );
    }
    return [];
});

const showAddPopup = ref<boolean>(false);
const addPopRef = shallowRef<InstanceType<typeof AddPop>>();

const queryParams = reactive({
    name: "",
    model_version: `${DigitalHumanModelVersionEnum.CHANJING},${DigitalHumanModelVersionEnum.STANDARD},${DigitalHumanModelVersionEnum.SHANJIAN}`,
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
<style scoped lang="scss">
:deep(.voice-search-input) {
    .el-input__wrapper {
        box-shadow: none;
        border: none;
        background-color: transparent;
    }
    .el-input__inner {
        @apply text-[13px] font-medium text-[#1E293B] pl-2;
        &::placeholder {
            @apply text-[#94A3B8];
        }
    }
}

.animate-spin {
    animation: spin 2s linear infinite;
}
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
