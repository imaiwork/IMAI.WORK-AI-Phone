<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header :content="route.query.name" @back="$router.back()" />
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="标题">
                    <el-input v-model="queryParams.material_title" @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="发布状态">
                    <el-select
                        class="!w-[180px]"
                        v-model="queryParams.status"
                        placeholder="请选择任务状态"
                        clearable
                        :empty-values="[undefined, null]"
                        @change="resetPage"
                        @keyup.enter="resetPage">
                        <el-option label="全部" value="" />
                        <el-option label="未发布" value="0" />
                        <el-option label="已发布" value="1" />
                        <el-option label="发布失败" value="2" />
                        <el-option label="发布中" value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="创作时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4 flex justify-between">
                <el-button
                    v-perms="[`ai_application.device.publish.${getDetailKey}.detail/delete`]"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
            </div>

            <el-table
                ref="tableRef"
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                row-key="id"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" reserve-selection />
                <el-table-column label="ID" prop="id" min-width="60" />
                <el-table-column label="发布标题" prop="material_title" min-width="140" show-overflow-tooltip />
                <el-table-column label="正文内容" prop="material_subtitle" min-width="180" show-overflow-tooltip />
                <el-table-column label="封面图" prop="pic" min-width="120">
                    <template #default="{ row }">
                        <image-contain
                            v-if="row.pic"
                            :src="row.pic"
                            :width="48"
                            :height="48"
                            fit="cover"
                            preview-teleported
                            :preview-src-list="getPreviewLists(row)" />
                    </template>
                </el-table-column>
                <el-table-column label="执行时间点" prop="exec_time" width="180">
                    <template #default="{ row }">
                        {{ row.exec_time || "-" }}
                    </template>
                </el-table-column>
                <el-table-column label="发布状态" width="120">
                    <template #default="{ row }">
                        {{ statusMap[Number(row.status)] || "-" }}
                    </template>
                </el-table-column>
                <el-table-column label="备注" prop="remark" min-width="100" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)"> 查看 </el-button>
                        <el-button
                            v-perms="[`ai_application.device.publish.${getDetailKey}.detail/delete`]"
                            type="danger"
                            link
                            @click="handleDelete(row.id)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
    <el-dialog v-model="showVideo" width="740px" title="详情">
        <div>
            <div class="flex flex-col gap-2">
                <div>
                    <div class="text-lg font-bold">标题</div>
                    <div class="mt-1">{{ currRow.material_title }}</div>
                </div>
                <div>
                    <div class="text-lg font-bold">正文</div>
                    <div class="mt-1">{{ currRow.material_subtitle }}</div>
                </div>
                <div>
                    <div class="text-lg font-bold">标签</div>
                    <div class="mt-1">
                        <div class="flex items-center gap-2" v-if="currRow.material_tag">
                            <div v-for="(topic, index) in currRow.material_tag.split(',')" :key="index">
                                <el-tag>{{ topic }}</el-tag>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-lg font-bold mb-1">{{ currRow.material_type == 1 ? "视频" : "图组" }}</div>
                    <video-player
                        v-if="currRow.material_type == 1"
                        ref="playerRef"
                        :src="currRow.material_url"
                        width="100%"
                        height="450px" />
                    <div class="grid grid-cols-6 gap-2" v-if="currRow.material_type == 2">
                        <div v-for="(img, index) in currRow.material_url.split(',')" :key="index">
                            <el-image :src="img" :preview-src-list="[img]" preview-teleported></el-image>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </el-dialog>
</template>

<script setup lang="ts">
import { getPublishRecordDetail, deletePublishRecord } from "@/api/ai_application/device/publish";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";

const route = useRoute();
const type = Number(route.query.type || 3);

const queryParams = reactive({
    material_title: "",
    publish_id: route.query.id,
    status: "",
    start_time: "",
    end_time: "",
});
const statusMap: any = {
    0: "等待中",
    1: "执行中",
    2: "执行完成",
    3: "执行失败",
    4: "中断",
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getPublishRecordDetail,
    params: queryParams,
});

const getDetailKey = computed(() => {
    const key = {
        1: "sph",
        3: "xhs",
        4: "dy",
        5: "ks",
    }[type];
    return key;
});

const getPreviewLists = (item: any) => {
    const { pic, material_type, material_url } = item;
    if (material_type == 1) {
        return [pic];
    } else {
        return material_url.split(",");
    }
};

const tableRef = ref<InstanceType<typeof ElTable>>();

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deletePublishRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const showVideo = ref(false);
const currRow = ref();
const handleDetail = (row: any) => {
    showVideo.value = true;
    currRow.value = row;
};

getLists();
</script>

<style scoped></style>
