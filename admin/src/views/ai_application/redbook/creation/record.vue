<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header content="发布记录" @back="$router.back()" />
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true" @submit.native.prevent>
                <el-form-item label="名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.material_title"
                        placeholder="请输入名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-table v-loading="pager.loading" :data="pager.lists" style="width: 100%">
                <el-table-column prop="material_title" label="名称" min-width="200"></el-table-column>
                <el-table-column prop="poi" label="POI" min-width="120"></el-table-column>
                <el-table-column prop="exec_time" label="执行时间点" width="180px">
                    <template #default="{ row }">
                        <div>{{ row.exec_time || "-" }}</div>
                    </template>
                </el-table-column>
                <el-table-column prop="status" label="状态" min-width="120">
                    <template #default="{ row }">
                        {{ statusMap[row.status] || "-" }}
                    </template>
                </el-table-column>
                <el-table-column prop="remark" label="失败原因" min-width="100px" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div>{{ row.remark || "-" }}</div>
                    </template>
                </el-table-column>
                <el-table-column prop="create_time" label="创建时间" width="180"></el-table-column>
                <el-table-column label="操作" width="100">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="previewMaterial(row)" :disabled="!row.material_url"
                            >预览</el-button
                        >
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists()"></pagination>
            </div>
        </el-card>

        <el-dialog v-model="showPreviewMaterial" width="740px" title="视频预览">
            <video-player
                ref="playerRef"
                :src="rowData.material_url"
                width="100%"
                height="450px"
                v-if="rowData.material_type === 1" />
            <img :src="rowData.material_url" class="max-h-[500px] mx-auto" v-else />
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { getPublishRecordList } from "@/api/ai_application/redbook";
import { usePaging } from "@/hooks/usePaging";

const route = useRoute();
const queryParams = reactive({
    type: 3,
    material_title: "",
    id: route.query.id as string,
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getPublishRecordList,
    params: queryParams,
});

const statusMap: Record<number, string> = {
    0: "未发布",
    1: "已发布",
    2: "发布失败",
    3: "发布中",
    4: "发布成功",
};

const showPreviewMaterial = ref(false);
const rowData = ref<any>();
const previewMaterial = async (row: any) => {
    rowData.value = row;

    showPreviewMaterial.value = true;
    await nextTick();
};

onMounted(() => {
    getLists();
});
</script>

<style scoped lang="scss"></style>
