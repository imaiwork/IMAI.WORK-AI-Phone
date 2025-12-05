<template>
    <div class="h-full flex flex-col">
        <div class="grow min-h-0 bg-app-bg-2 rounded-[20px] flex flex-col">
            <div class="flex-shrink-0 px-[14px]">
                <ElScrollbar>
                    <div class="flex items-center justify-between h-[88px] gap-[14px]">
                        <div class="flex items-center gap-2 cursor-pointer" @click="emit('back')">
                            <Icon name="el-icon-ArrowLeft" color="#ffffff"></Icon>
                            <div class="text-white">返回</div>
                        </div>
                        <div class="flex items-center gap-[14px]">
                            <ElInput
                                v-model="queryParams.material_title"
                                prefix-icon="el-icon-Search"
                                class="!w-[240px] search-name-input"
                                placeholder="请输入标题"
                                clearable
                                @clear="getLists()"
                                @keydown.enter="getLists()">
                                <template #append>
                                    <ElButton @click="getLists()"> 搜索 </ElButton>
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
            <div class="grow min-h-0">
                <ElTable
                    v-loading="pager.loading"
                    :data="pager.lists"
                    height="100%"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '60px' }">
                    <ElTableColumn prop="material_title" label="标题" min-width="120px" show-overflow-tooltip>
                        <template #default="{ row }">
                            <div>{{ row.material_title || "-" }}</div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="material_subtitle" label="正文" min-width="160px" show-overflow-tooltip>
                        <template #default="{ row }">
                            <div>{{ row.material_subtitle || "-" }}</div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="封面图" width="120px">
                        <template #default="{ row }">
                            <ElImage
                                class="h-[80px]"
                                v-if="row.pic"
                                fit="cover"
                                :src="row.pic"
                                :preview-src-list="[row.pic]"
                                preview-teleported></ElImage>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="exec_time" label="执行时间点" width="180px">
                        <template #default="{ row }">
                            <div>{{ row.exec_time || "-" }}</div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="发布状态" width="120px">
                        <template #default="{ row }">
                            {{ statusMap[row.status] || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="remark" label="备注" min-width="100px" show-overflow-tooltip>
                        <template #default="{ row }">
                            <div>{{ row.remark || "-" }}</div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="操作" width="160px" fixed="right">
                        <template #default="{ row }">
                            <div class="">
                                <ElButton type="primary" link @click="handleDetail(row)">查看</ElButton>
                                <ElButton type="primary" link @click="handleDownLoad(row)">下载</ElButton>
                                <ElButton
                                    v-if="row.status == 1 || !isPublish(row)"
                                    type="danger"
                                    link
                                    @click="handleDelete(row)"
                                    >删除</ElButton
                                >
                            </div>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <ElEmpty description="暂无数据"></ElEmpty>
                    </template>
                </ElTable>
            </div>
            <div class="flex justify-center p-4">
                <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
            </div>
        </div>
    </div>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false"></preview-video>
    <ElImageViewer
        v-if="showPreviewImage"
        ref="previewImageRef"
        :url-list="previewImages"
        :initial-index="0"
        @close="showPreviewImage = false" />
</template>

<script setup lang="ts">
import { getDeviceTaskRecordList, deleteDeviceTaskRecord } from "@/api/device";
import dayjs from "dayjs";
import { PublishTaskTypeEnum } from "~/pages/app/matrix/_enums";

const props = withDefaults(
    defineProps<{
        type?: PublishTaskTypeEnum;
    }>(),
    {
        type: PublishTaskTypeEnum.VIDEO,
    }
);

const emit = defineEmits<{
    (e: "back"): void;
    (e: "copy", id: string): void;
}>();

const nuxtApp = useNuxtApp();
const query = searchQueryToObject();
const queryParams = reactive({
    publish_id: query.publish_id,
    task_type: 3,
    material_title: "",
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getDeviceTaskRecordList,
    params: queryParams,
});

const statusMap = {
    0: "等待中",
    1: "执行中",
    2: "执行完成",
    3: "执行失败",
    4: "中断",
};

const previewVideoRef = shallowRef();
const previewImageRef = shallowRef();
const showPreviewVideo = ref(false);
const showPreviewImage = ref(false);
const previewImages = ref([]);

// 判断是否允许重新更新时间和重新发布
const isPublish = (row) => {
    return dayjs(row.publish_end).isAfter(dayjs());
};

const handleDetail = async (row: any) => {
    const { material_type, material_url } = row;
    showPreviewVideo.value = true;
    await nextTick();
    if (material_type == 1) {
        previewVideoRef.value?.open();
        previewVideoRef.value?.setUrl(material_url);
    }

    if (material_type == 2) {
        showPreviewImage.value = true;
        await nextTick();
        previewImages.value = material_url.split(",");
    }
};

const handleDownLoad = (row: any) => {
    const { material_type, material_url } = row;
    if (material_type == 1) {
        downloadFile(material_url);
    }
    if (material_type == 2) {
        const imgs = material_url.split(",");
        imgs.forEach((item) => {
            downloadFile(item);
        });
    }
};

const handleDelete = (row: any) => {
    nuxtApp.$confirm({
        message: "确定删除该记录吗？",
        onConfirm: async () => {
            try {
                await deleteDeviceTaskRecord(row.id);
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgWarning("删除失败");
            }
        },
    });
};

onMounted(() => {
    getLists();
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
