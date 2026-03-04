<template>
    <div class="h-full flex flex-col bg-white rounded-[20px] border border-br overflow-hidden min-w-[1000px]">
        <div class="flex-shrink-0 px-8 h-[88px] flex items-center justify-between">
            <div class="flex flex-col">
                <h1 class="text-xl font-[900] text-gray-950">发布任务管理</h1>
                <p class="text-xs text-tx-placeholder font-medium mt-0.5">监控所有账号的视频与图文发布进度</p>
            </div>

            <div class="flex items-center gap-3">
                <ElSelect
                    v-model="queryParams.media_type"
                    class="!w-[140px] custom-select-pill"
                    placeholder="发布类型"
                    clearable
                    :show-arrow="false"
                    :empty-values="[null, undefined]"
                    @change="getLists()">
                    <ElOption label="全部类型" value=""></ElOption>
                    <ElOption label="视频素材" value="1"></ElOption>
                    <ElOption label="图片素材" value="2"></ElOption>
                </ElSelect>

                <div
                    class="flex items-center rounded-full h-11 border border-br px-1.5 bg-gray-50/50 transition-all bg-white focus-within:border-primary focus-within:bg-white">
                    <ElInput
                        v-model="queryParams.name"
                        class="search-input-clean !w-[180px]"
                        placeholder="搜索任务名称..."
                        clearable
                        @clear="resetPage"
                        @keyup.enter="resetPage">
                        <template #prefix>
                            <Icon name="el-icon-Search" :size="16" color="#94a3b8" />
                        </template>
                    </ElInput>
                    <ElButton
                        type="primary"
                        class="!rounded-full !h-8 !px-5 !text-xs !font-black shadow-sm"
                        @click="resetPage">
                        搜索
                    </ElButton>
                </div>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElTable height="100%" :data="pager.lists" v-loading="pager.loading">
                <ElTableColumn prop="name" label="任务名称" width="220" fixed="left">
                    <template #default="{ row }">
                        <div class="flex items-center gap-2 group cursor-pointer" @click="handleEdit(row)">
                            <span class="text-gray-950 font-black truncate max-w-[160px]">{{ row.name }}</span>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <Icon name="local-icon-edit" :size="14" color="var(--color-primary)" />
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="发布账号" min-width="180">
                    <template #default="{ row }">
                        <div class="flex justify-center">
                            <div
                                class="flex justify-center items-center gap-2 px-2 py-1 rounded-lg bg-gray-50 border border-br-extra-light w-fit">
                                <img :src="getPlatform(row.account_type)?.icon" class="w-4 h-4 object-contain" />
                                <span class="text-xs font-medium text-tx-regular line-clamp-1">{{ row.account }}</span>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="配置详情" width="160">
                    <template #default="{ row }">
                        <div class="flex justify-center items-center flex-col gap-0.5">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] font-black text-tx-placeholder uppercase">类型:</span>
                                <span class="text-xs font-medium text-tx-secondary">{{
                                    row.auto_type == 0 ? "手动" : "24h任务"
                                }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] font-black text-tx-placeholder uppercase">素材:</span>
                                <span class="text-xs font-medium text-primary">{{
                                    row.media_type == 1 ? "视频" : "图片"
                                }}</span>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="任务状态" width="120" align="center">
                    <template #default="{ row }">
                        <div class="status-badge" :class="row.status == 1 ? 'is-running' : 'is-success'">
                            <span class="badge-dot"></span>
                            <span class="badge-text">{{ row.status == 1 ? "进行中" : "已完成" }}</span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="执行周期" width="180">
                    <template #default="{ row }">
                        <div class="flex justify-center items-center flex-col text-[11px]">
                            <div class="flex items-center gap-1 text-tx-secondary">
                                <span class="font-medium">{{ row.publish_start }}</span>
                                <span class="text-tx-placeholder">始</span>
                            </div>
                            <div class="flex items-center gap-1 text-tx-secondary">
                                <span class="font-medium">{{ row.publish_end }}</span>
                                <span class="text-tx-placeholder">终</span>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="进度/周期" width="120" align="center">
                    <template #default="{ row }">
                        <div class="flex flex-col items-center">
                            <div class="text-sm font-black text-gray-950">
                                {{ row.published_count }}<span class="text-tx-placeholder font-medium mx-0.5">/</span
                                >{{ row.count }}
                            </div>
                            <div class="text-[10px] font-medium text-primary bg-[#0065fb]/10 px-1.5 rounded">
                                {{ getPublishCycle(row) }}
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn
                    prop="create_time"
                    label="创建时间"
                    width="160"
                    class-name="text-xs text-tx-placeholder" />

                <ElTableColumn label="操作" width="100" fixed="right" align="right">
                    <template #default="{ row }">
                        <div class="flex justify-end items-center gap-1">
                            <ElButton type="primary" link size="small" class="!font-black" @click="handleDetail(row)"
                                >详情</ElButton
                            >
                            <div v-if="row.auto_type == 0" class="w-[1px] h-3 bg-br-extra-light mx-1"></div>
                            <ElButton
                                v-if="row.auto_type == 0"
                                type="danger"
                                link
                                size="small"
                                @click="handleDelete(row.id)"
                                >删除</ElButton
                            >
                        </div>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <div class="py-20">
                        <ElEmpty description="暂无发布任务数据" />
                    </div>
                </template>
            </ElTable>
        </div>
        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between">
            <div class="text-xs font-medium text-[#CBD5E1]">共计 {{ pager.count }} 个分发任务已就绪</div>
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
        </div>
    </div>
    <rename-pop
        v-if="showRename"
        ref="renamePopupRef"
        :fetch-fn="updateDeviceAccountTask"
        @close="showRename = false"
        @success="getLists()"></rename-pop>
    <Detail v-if="showDetail" ref="detailPopupRef" @close="showDetail = false" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { getDeviceAccountTaskList, deleteDeviceAccountTask, updateDeviceAccountTask } from "@/api/device";
import Detail from "./detail.vue";

const { getPlatform } = useSocialPlatform();

const { query } = useRoute();

const queryParams = reactive({
    name: "",
    task_type: 3,
    page_size: 20,
    media_type: "",
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getDeviceAccountTaskList,
    params: queryParams,
});

const showDetail = ref(false);
const detailPopupRef = ref<InstanceType<typeof Detail>>();

const showRename = ref(false);
const renamePopupRef = shallowRef();

// 获取发布周期
const getPublishCycle = (row: any) => {
    const { publish_start, publish_end } = row;
    if (publish_start && publish_end) {
        return dayjs(publish_end).diff(dayjs(publish_start), "day") + 1 + "天";
    }
    return "-";
};

const handleDetail = async (row: any) => {
    showDetail.value = true;
    await nextTick();
    detailPopupRef.value.open(row);
};

const handleEdit = async (row: any) => {
    showRename.value = true;
    await nextTick();
    renamePopupRef.value.open();
    renamePopupRef.value.setFormData({ id: row.id, name: row.name });
};

// 添加发布视频 End
const handleDelete = async (id) => {
    useNuxtApp().$confirm({
        message: "是否删除该任务？",
        onConfirm: async () => {
            try {
                await deleteDeviceAccountTask({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

getLists();
</script>

<style lang="scss" scoped>
.search-input-clean {
    :deep(.el-input__wrapper) {
        border: none;
        background: transparent;
        box-shadow: none;
    }
}
:deep(.custom-select-pill) {
    .el-select__wrapper {
        border-radius: 99px !important;
        height: 40px !important;
        &.is-focus {
            box-shadow: 0 0 0 1px #4f46e5 inset !important;
        }
    }
}
.status-badge {
    @apply inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-black;
    .badge-dot {
        @apply w-1.5 h-1.5 rounded-full bg-[currentColor];
    }

    &.is-running {
        @apply bg-blue-50 text-primary border-blue-100;
        .badge-dot {
            @apply animate-pulse;
        }
    }
    &.is-success {
        @apply bg-green-50 text-green-600 border-green-100;
    }
}
</style>
