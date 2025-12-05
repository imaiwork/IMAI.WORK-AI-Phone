<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.user"
                        placeholder="请输入用户昵称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="生成模型">
                    <el-select
                        v-model="queryParams.model_type"
                        class="!w-[160px]"
                        placeholder="请选择生成模型"
                        clearable>
                        <el-option
                            v-for="(item, index) in modelList"
                            :key="index"
                            :label="item.name"
                            :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="创作类型">
                    <el-select
                        v-model="type"
                        class="!w-[160px]"
                        placeholder="请选择创作类型"
                        clearable
                        @change="changeType">
                        <el-option label="文生图" value="3" />
                        <el-option label="图生图" value="4" />
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
                    v-perms="['ai_application.draw_sd.record/delete']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
                <div>
                    <el-button
                        v-perms="['ai_application.draw_sd.record/edit']"
                        type="primary"
                        @click="handlePromptConfig('image')">
                        图片获取提示词配置
                    </el-button>
                    <el-button
                        v-perms="['ai_application.draw_sd.record/edit']"
                        type="primary"
                        @click="handlePromptConfig('text')">
                        文字获取提示词配置
                    </el-button>
                </div>
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
                <el-table-column label="生成模型" prop="model_type" min-width="120">
                    <template #default="{ row }">
                        <div class="whitespace-pre">
                            {{ modelList.find((item: any) => item.id == row.model_type)?.name }}
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="生成图" min-width="120">
                    <template #default="{ row }">
                        <div class="flex" v-if="row.images && row.images.length > 0">
                            <image-contain
                                :src="row.images[0].image"
                                :width="50"
                                :height="50"
                                :preview-src-list="row.images.map((item) => item.image)"
                                preview-teleported
                                fit="contain" />
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="创作类型" prop="type_name" min-width="120" />
                <el-table-column label="消耗算力" prop="points" min-width="120">
                    <template #default="{ row }"> {{ row.points || 0 }}算力 </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button
                            v-perms="['ai_application.draw_sd.record/delete']"
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
    <prompt-pop ref="promptPopRef" v-if="showPrompt" @close="showPrompt = false" />
    <detail-pop ref="detailPopupRef" v-if="showDetail" @close="showDetail = false" />
</template>
<script lang="ts" setup>
import { getDrawRecordList, delDrawRecord } from "@/api/ai_application/draw/draw_records";
import useAppStore from "@/stores/modules/app";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import PromptPop from "./prompt.vue";
import DetailPop from "./detail.vue";
import { ElTable } from "element-plus";

const appStore = useAppStore();
const modelList = computed(() => appStore.config.draw?.channel);

const type = ref();

const queryParams = reactive({
    user: "",
    model_type: "",
    start_time: "",
    end_time: "",
    type: [3, 4],
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getDrawRecordList,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const changeType = (val: any) => {
    queryParams.type = [val];
};

const promptPopRef = shallowRef<InstanceType<typeof PromptPop> | null>(null);
const showPrompt = ref(false);
const detailPopupRef = shallowRef<InstanceType<typeof DetailPop> | null>(null);
const showDetail = ref(false);

const handlePromptConfig = async (mode: string) => {
    showPrompt.value = true;
    await nextTick();
    promptPopRef.value?.open(mode);
};

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDetail = async (row: any) => {
    showDetail.value = true;
    await nextTick();
    detailPopupRef.value?.open(row);
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await delDrawRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
