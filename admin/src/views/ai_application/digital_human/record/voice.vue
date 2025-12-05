<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="音色名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入音色名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="使用模型">
                    <el-select v-model="queryParams.model_version" class="!w-[180px]" placeholder="请选择使用模型">
                        <el-option v-for="item in modelChannel" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="创建时间">
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
                    v-perms="['dh_record.voice/del']"
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
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="音色名称" prop="name" min-width="180" show-overflow-tooltip />
                <el-table-column label="使用模型" width="120">
                    <template #default="{ row }">
                        {{ getModelName(row.model_version) }}
                    </template>
                </el-table-column>
                <el-table-column label="模型性别" min-width="120" show-overflow-tooltip>
                    <template #default="{ row }">
                        {{ row.gender == "male" ? "男" : "女" }}
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="points" min-width="100">
                    <template #default="{ row }"> {{ row.status == 2 ? 0 : row.points || 0 }}算力 </template>
                </el-table-column>
                <el-table-column label="生成状态" min-width="120">
                    <template #default="{ row }">
                        <template v-if="[0, 3, 4, 5].includes(row.status)">
                            <el-tag type="warning">克隆中</el-tag>
                        </template>
                        <template v-else-if="row.status == 1">
                            <el-tag type="success">成功</el-tag>
                        </template>
                        <template v-else-if="row.status == 2">
                            <el-tag type="danger">失败</el-tag>
                        </template>
                    </template>
                </el-table-column>
                <el-table-column label="建立时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handlePlay(row)"> 播放 </el-button>
                        <el-button v-perms="['dh_record.voice/del']" type="danger" link @click="handleDelete([row.id])">
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
</template>
<script lang="ts" setup>
import { getVoiceChatRecord, deleteVoiceChatRecord } from "@/api/ai_application/digital_human/record";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import useAppStore from "@/stores/modules/app";
import { ElTable } from "element-plus";

const appStore = useAppStore();
const { config } = toRefs(appStore);
const modelChannel = computed(() => config.value?.digital_human.channel);

const queryParams = reactive({
    name: "",
    start_time: "",
    end_time: "",
    model_version: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getVoiceChatRecord,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const getModelName = (model_version: string) => {
    return modelChannel.value.find((item: any) => item.id == model_version)?.name;
};

const handlePlay = async (row: any) => {
    const { voice_urls } = row;
    if (!voice_urls) {
        feedback.msgError("暂无音频");
        return;
    }
    window.open(voice_urls);
};

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteVoiceChatRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
