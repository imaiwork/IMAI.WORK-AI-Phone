<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.nickname"
                        placeholder="请输入用户"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <!-- 任务类型 -->
                <el-form-item label="任务类型">
                    <el-select
                        class="!w-[180px]"
                        v-model="queryParams.media_type"
                        placeholder="请选择任务类型"
                        clearable
                        :empty-values="[undefined, null]"
                        @change="resetPage"
                        @keyup.enter="resetPage">
                        <el-option label="全部" value="" />
                        <el-option label="视频" value="1" />
                        <el-option label="图片" value="2" />
                    </el-select>
                </el-form-item>
                <!-- 任务状态 -->
                <el-form-item label="任务状态">
                    <el-select
                        class="!w-[180px]"
                        v-model="queryParams.status"
                        placeholder="请选择任务状态"
                        clearable
                        :empty-values="[undefined, null]"
                        @change="resetPage"
                        @keyup.enter="resetPage">
                        <el-option label="全部" value="" />
                        <el-option label="等待处理" :value="1" />
                        <el-option label="执行中" :value="2" />
                        <el-option label="已完成" :value="3" />
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
                    v-perms="[`ai_application.device.publish.${getDetailKey}/delete`]"
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
                <el-table-column label="创建用户" prop="user_nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="发布账号" prop="account" min-width="140" show-overflow-tooltip>
                    <template #default="{ row }">
                        {{ row.account || "-" }}
                    </template>
                </el-table-column>
                <el-table-column label="任务名称" prop="name" min-width="180" show-overflow-tooltip />
                <el-table-column label="任务类型" width="100">
                    <template #default="{ row }">
                        {{ row.media_type == 1 ? "视频" : "图片" }}
                    </template>
                </el-table-column>
                <el-table-column label="任务状态" min-width="120">
                    <template #default="{ row }">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-[6px] h-[6px] rounded-full"
                                :class="{
                                    'bg-[#FFBC50]': (row.status = 1),
                                    'bg-primary': row.status == 2,
                                    'bg-[#3BB840]': row.status == 3,
                                }"></div>
                            <div v-if="row.status == 1">待执行</div>
                            <div v-else-if="row.status == 2">发布中</div>
                            <div v-else-if="row.status == 3">已完成</div>
                            <div v-else>-</div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="发布周期" min-width="100">
                    <template #default="{ row }">{{ row.publish_cycle }} 天</template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="[`ai_application.device.publish.${getDetailKey}/detail`]"
                            type="primary"
                            link>
                            <router-link
                                :to="{
                                    path: getRoutePath(`ai_application.device.publish.${getDetailKey}/detail`),
                                    query: {
                                        id: row.publish_id,
                                        name: row.name,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="[`ai_application.device.publish.${getDetailKey}/delete`]"
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
</template>
<script lang="ts" setup>
import { getPublishList, deletePublish } from "@/api/ai_application/device/publish";
import { getRoutePath } from "@/router";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";

const route = useRoute();
const type = Number(route.query.type || 3);

const queryParams = reactive({
    start_time: "",
    end_time: "",
    nickname: "",
    media_type: "",
    status: "",
    account_type: type,
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getPublishList,
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

const tableRef = ref<InstanceType<typeof ElTable>>();

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deletePublish({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
