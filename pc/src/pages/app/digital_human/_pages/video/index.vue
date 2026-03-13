<template>
    <div class="h-full flex flex-col bg-white rounded-[32px] overflow-hidden border border-br min-w-[1000px]">
        <div class="flex-shrink-0 px-6 border-b border-br">
            <div class="flex items-center justify-between h-[80px]">
                <div class="flex items-center gap-x-3">
                    <div class="w-12 h-12 flex items-center justify-center rounded-[16px] bg-[#0065FB]/5 text-primary">
                        <Icon name="el-icon-VideoCamera" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[16px] text-[#1E293B] font-black tracking-tight">视频创作记录</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Video Creation Record
                        </div>
                    </div>
                </div>
                <ElSelect
                    v-model="queryParams.type"
                    placeholder="视频类型"
                    class="custom-select !w-[150px]"
                    :show-arrow="false"
                    clearable
                    @clear="reset()"
                    @change="reset()">
                    <ElOption label="全部类型" value="" />
                    <ElOption label="数字人" :value="CreateVideoTypeEnum.DIGITAL_HUMAN" />
                    <ElOption label="口播" :value="CreateVideoTypeEnum.ORAL_MIX" />
                    <ElOption label="真人" :value="CreateVideoTypeEnum.REAL_PERSON_MIXING" />
                    <ElOption label="素材" :value="CreateVideoTypeEnum.MATERIAL_MIX" />
                    <ElOption label="新闻" :value="CreateVideoTypeEnum.NEWS" />
                    <ElOption label="一句话" :value="CreateVideoTypeEnum.SENTENCE" />
                    <ElOption label="分镜" :value="CreateVideoTypeEnum.STORYBOARD" />
                </ElSelect>
            </div>
        </div>
        <div class="grow min-h-0 relative" v-spin="{ show: loading, text: loadingText }">
            <ElScrollbar :distance="20" @end-reached="load">
                <div class="p-4">
                    <div v-if="pager.lists.length">
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                            <div
                                v-for="(item, index) in pager.lists"
                                class="relative cursor-pointer overflow-hidden"
                                :key="index">
                                <video-item is-create :item="item" @delete="handleDelete" @retry="handleRetry" />
                            </div>
                        </div>
                        <load-text :is-load="pager.isLoad"></load-text>
                    </div>
                    <div class="h-full flex items-center justify-center" v-else>
                        <ElEmpty />
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between border-t border-br">
            <div class="text-xs font-medium text-[#CBD5E1]">共计 {{ pager.count }} 条视频创作记录</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getVideoCreationRecord, deleteVideoCreationRecord } from "@/api/app";
import { retrySoraTask } from "@/api/digital_human";
import { CreateVideoTypeEnum } from "@/pages/app/digital_human/_enums";
import VideoItem from "@/pages/app/digital_human/_components/video-item.vue";

const queryParams = reactive({
    page_no: 1,
    page_size: 20,
    type: "",
});
const loading = ref(false);
const loadingText = ref("加载中...");

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getVideoCreationRecord,
    params: queryParams,
    isScroll: true,
});

const reset = async () => {
    loading.value = true;
    try {
        await resetPage();
    } finally {
        loading.value = false;
    }
};

const load = async (e: any) => {
    if (e == "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no++;
        await getLists();
    }
};

const handleDelete = async (data: any) => {
    useNuxtApp().$confirm({
        message: "确定删除吗？",
        onConfirm: async () => {
            try {
                await deleteVideoCreationRecord({ id: data.id, task_id: data.task_id, type: data.type });
                pager.lists = pager.lists.filter((item) => item.id !== data.id);
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError("删除失败");
            }
        },
    });
};

const handleRetry = async (data: any) => {
    useNuxtApp().$confirm({
        message: "确定重试吗？",
        onConfirm: async () => {
            try {
                await retrySoraTask({ id: data.id });
                feedback.msgSuccess("重试成功");
                getData();
            } catch (error) {
                feedback.msgError(error || "重试失败");
            }
        },
    });
};

const getData = async () => {
    loading.value = true;
    try {
        await getLists();
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    getData();
});
</script>

<style scoped lang="scss">
:deep(.el-checkbox) {
    .el-checkbox__inner {
        background-color: transparent;
        border-color: var(--el-color-danger);
        &::after {
            border-color: var(--el-color-danger);
        }
    }
    .el-checkbox__label {
        color: #fff;
        font-size: var(--el-font-size-base);
    }
}
</style>
