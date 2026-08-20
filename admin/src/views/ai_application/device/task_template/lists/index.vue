<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="模版名称" prop="name">
                    <el-input v-model="queryParams.name" placeholder="请输入模版名称" />
                </el-form-item>
                <el-form-item label="分类名称" prop="category_name">
                    <el-input v-model="queryParams.category_name" placeholder="请输入分类名称" />
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-select
                        v-model="queryParams.status"
                        class="!w-[120px]"
                        :empty-values="[null, undefined]"
                        @change="getLists()">
                        <el-option label="全部" value="" />
                        <el-option label="开启" value="1" />
                        <el-option label="关闭" value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="创建时间">
                    <daterange-picker
                        class="w-[280px]"
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
            <div class="mb-4">
                <el-button v-perms="['ai_application.task_template.lists/add']" type="primary" @click="handleAdd"
                    >新增</el-button
                >
            </div>
            <el-table :data="pager.lists" v-loading="pager.loading">
                <el-table-column prop="id" label="ID" min-width="80" fixed="left" />
                <el-table-column prop="name" label="模版名称" min-width="200" show-overflow-tooltip />
                <el-table-column prop="category_name" label="分类名称" min-width="200" show-overflow-tooltip />
                <el-table-column prop="schedule_count" label="包含任务数" width="100" />
                <el-table-column prop="type" label="模版类型" width="100">
                    <template #default="{ row }">
                        <span v-if="row.operation_preference == 1">综合</span>
                        <span v-else-if="row.operation_preference == 2">获客</span>
                        <span v-else-if="row.operation_preference == 3">养号</span>
                        <span v-else-if="row.operation_preference == 4">运营</span>
                    </template>
                </el-table-column>
                <el-table-column label="来源" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.type == 1" type="info">专属</el-tag>
                        <el-tag v-else-if="row.type == 2" type="success">用户</el-tag>
                        <el-tag v-else-if="row.type == 3" type="warning">系统</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="140">
                    <template #default="{ row }">
                        <el-switch
                            v-if="row.type != 1"
                            v-perms="['ai_application.task_template.cate/status']"
                            @change="changeStatus(row)"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0" />
                    </template>
                </el-table-column>
                <el-table-column prop="create_time" label="创建时间" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-if="row.type != 1"
                            v-perms="['ai_application.task_template.lists/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                            >编辑</el-button
                        >
                        <el-button
                            v-if="row.type != 1"
                            v-perms="['ai_application.task_template.lists/delete']"
                            type="danger"
                            link
                            @click="handleDel(row.id)"
                            >删除</el-button
                        >
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
    <edit-popup v-if="showEdit" ref="editRef" @close="showEdit = false" @success="getLists" />
</template>

<script setup lang="ts">
import {
    getTaskTemplateList,
    deleteTaskTemplate,
    editTaskTemplate,
    updateTaskTemplateStatus,
} from "@/api/ai_application/device/task_template";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import EditPopup from "./edit.vue";

const editRef = shallowRef<InstanceType<typeof EditPopup>>();

const showEdit = ref(false);
const queryParams = reactive({
    name: "",
    status: "",
    category_name: "",
    start_time: "",
    end_time: "",
});
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getTaskTemplateList,
    params: queryParams,
});

const handleAdd = async () => {
    showEdit.value = true;
    await nextTick();
    await editRef.value?.open("add");
};

const handleEdit = async (row: any) => {
    showEdit.value = true;
    await nextTick();
    await editRef.value?.open("edit");
    await editRef.value?.getDetail(row.id);
};

const handleDel = async (id: string) => {
    await feedback.confirm("确定要删除吗？");
    await deleteTaskTemplate({ id });
    getLists();
};

const changeStatus = async (row: any) => {
    try {
        await updateTaskTemplateStatus({ id: row.id, status: row.status });
    } finally {
        getLists();
    }
};

getLists();
</script>

<style scoped></style>
