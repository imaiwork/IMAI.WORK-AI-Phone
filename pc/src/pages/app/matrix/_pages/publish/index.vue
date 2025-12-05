<template>
    <div class="h-full flex flex-col bg-app-bg-2 rounded-[20px]">
        <div class="flex-shrink-0 px-[14px]">
            <ElScrollbar>
                <div class="flex items-center justify-end h-[88px]">
                    <div class="flex items-center gap-[14px]">
                        <!-- 发布类型 -->
                        <div>
                            <ElSelect
                                v-model="queryParams.media_type"
                                class="!w-[160px]"
                                placeholder="请选择发布类型"
                                clearable
                                popper-class="dark-select-popper"
                                :show-arrow="false"
                                :empty-values="[null, undefined]"
                                @clear="getLists()"
                                @change="getLists()">
                                <ElOption label="全部" value=""></ElOption>
                                <ElOption label="视频" value="1"></ElOption>
                                <ElOption label="图片" value="2"></ElOption>
                            </ElSelect>
                        </div>
                        <ElInput
                            v-model="queryParams.name"
                            prefix-icon="el-icon-Search"
                            class="!w-[240px] search-name-input"
                            placeholder="请输入任务名称"
                            clearable
                            @clear="getLists()"
                            @keydown.enter="getLists()">
                            <template #append>
                                <ElButton text @click="getLists()"> 搜索 </ElButton>
                            </template>
                        </ElInput>
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
        <div class="grow min-h-0 overflow-hidden flex flex-col">
            <div class="grow min-h-0">
                <ElTable
                    height="100%"
                    :data="pager.lists"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '50px' }"
                    v-loading="pager.loading">
                    <ElTableColumn prop="name" label="任务名称" width="240" fixed="left">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-2 cursor-pointer" @click="handleEdit(row)">
                                <div class="text-white">{{ row.name }}</div>
                                <Icon name="local-icon-edit" color="#ffffff" />
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="发布账号" propas="account" min-width="200" show-overflow-tooltip>
                        <template #default="{ row }">
                            <div class="flex justify-center items-center gap-x-1">
                                <img :src="getPlatform(row.account_type)?.icon" class="w-4 h-4" />
                                <span>{{ row.account }}</span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="发布类型" width="80">
                        <template #default="{ row }">
                            {{ row.media_type == 1 ? "视频" : "图片" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="任务状态" width="120">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-2">
                                <div
                                    class="w-[6px] h-[6px] rounded-full"
                                    :class="{
                                        'bg-primary': row.status == 1,
                                        'bg-[#3BB840]': row.status == 2,
                                    }"></div>
                                <div v-if="row.status == 1">进行中</div>
                                <div v-else-if="row.status == 2">已完成</div>
                                <div v-else>-</div>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="发布周期" min-width="100">
                        <template #default="{ row }">
                            <div>{{ getPublishCycle(row) }}</div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="发布进度" width="100">
                        <template #default="{ row }"> {{ row.published_count }} / {{ row.count }} </template>
                    </ElTableColumn>
                    <ElTableColumn prop="create_time" label="创建时间" width="180"></ElTableColumn>
                    <ElTableColumn label="操作" width="120" fixed="right" align="right">
                        <template #default="{ row }">
                            <div class="flex justify-end items-center">
                                <ElButton
                                    class="!border-app-border-2"
                                    color="#181818"
                                    size="small"
                                    @click="handleDetail(row)"
                                    >详情</ElButton
                                >
                                <ElButton type="danger" link size="small" @click="handleDelete(row.id)">删除</ElButton>
                            </div>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <ElEmpty />
                    </template>
                </ElTable>
            </div>
            <div class="flex justify-center p-4">
                <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
            </div>
        </div>
    </div>
    <EditPopup v-if="showEdit" ref="editPopupRef" @close="showEdit = false" @success="getLists()" />
    <Detail v-if="showDetail" ref="detailPopupRef" @close="showDetail = false" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { getDeviceAccountTaskList, deleteDeviceAccountTask } from "@/api/device";
import { PublishTaskTypeEnum } from "@/pages/app/matrix/_enums";
import EditPopup from "./edit.vue";
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

const showEdit = ref(false);
const editPopupRef = ref<InstanceType<typeof EditPopup>>();

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
    showEdit.value = true;
    await nextTick();
    editPopupRef.value.open();
    editPopupRef.value.setFormData(row);
};

// 添加发布视频 End
const handleDelete = async (id) => {
    useNuxtApp().$confirm({
        message: "是否删除该任务？",
        theme: "dark",
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

<style scoped></style>
