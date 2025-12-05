<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="形象名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入形象名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.user"
                        placeholder="请输入用户"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="使用模型">
                    <el-select v-model="queryParams.model_version" class="!w-[180px]" placeholder="请选择使用模型">
                        <el-option v-for="item in modelChannel" :key="item.id" :label="item.name" :value="item.id" />
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
                    v-perms="['dh_record.anchor/del']"
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
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="形象名称" prop="name" min-width="180" show-overflow-tooltip />
                <el-table-column label="封面图" min-width="120">
                    <template #default="{ row }">
                        <div class="flex items-center">
                            <image-contain
                                v-if="row.pic"
                                :src="row.pic"
                                radius="8"
                                :width="60"
                                :height="60"
                                :preview-src-list="[row.pic]"
                                preview-teleported
                                fit="cover" />
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="使用模型" width="120">
                    <template #default="{ row }">
                        {{ getModelName(row.model_version) }}
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="points" min-width="100">
                    <template #default="{ row }"> {{ row.status == 2 ? 0 : row.points || 0 }}算力 </template>
                </el-table-column>
                <el-table-column label="当前状态" min-width="120">
                    <template #default="{ row }">
                        <template v-if="row.status == 1">
                            <el-tag type="success">生成成功</el-tag>
                        </template>
                        <template v-else-if="row.status == 2">
                            <el-tag type="danger">生成失败</el-tag>
                        </template>
                        <template v-else-if="[0, 5].includes(row.status)">
                            <el-tag type="warning">生成中</el-tag>
                        </template>
                    </template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handlePlay(row)"> 播放 </el-button>
                        <el-button
                            v-perms="['dh_record.anchor/del']"
                            type="danger"
                            link
                            @click="handleDelete([row.id])">
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
    <el-dialog v-model="showVideo" width="740px" title="视频预览">
        <video-player ref="playerRef" :src="videoUrl" width="100%" height="450px" />
    </el-dialog>
</template>
<script lang="ts" setup>
import { getAnchorRecord, deleteAnchorRecord } from "@/api/ai_application/digital_human/record";
import { usePaging } from "@/hooks/usePaging";
import useAppStore from "@/stores/modules/app";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";

const appStore = useAppStore();
const { config } = toRefs(appStore);
const modelChannel = computed(() => config.value?.digital_human.channel);

const queryParams = reactive({
    name: "",
    start_time: "",
    end_time: "",
    user: "",
    model_version: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getAnchorRecord,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const getModelName = (model_version: string) => {
    return modelChannel.value.find((item: any) => item.id == model_version)?.name;
};

const showVideo = ref(false);
const videoUrl = ref("");
const handlePlay = async (row: any) => {
    const { url } = row;
    showVideo.value = true;
    videoUrl.value = url;
};

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteAnchorRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
