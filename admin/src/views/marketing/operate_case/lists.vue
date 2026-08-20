<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="教程名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.title"
                        placeholder="请输入教程名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select
                        class="!w-[120px]"
                        v-model="queryParams.status"
                        placeholder="请选择状态"
                        clearable
                        :empty-values="[null, undefined]">
                        <el-option label="全部" value="" />
                        <el-option label="启用" value="1" />
                        <el-option label="禁用" value="0" />
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
            <div class="flex justify-between">
                <el-button v-perms="['marketing.operate_case.lists/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增
                </el-button>
            </div>
            <el-table
                size="large"
                class="mt-4"
                v-loading="pager.loading"
                row-key="id"
                :data="pager.lists"
                :tree-props="{ children: 'sub_list' }">
                <el-table-column label="教程标题" prop="title" min-width="180" />
                <el-table-column label="类型" prop="category_type_text" min-width="120" />
                <el-table-column label="线索量" prop="leads" min-width="120" />
                <el-table-column label="意向客户" prop="convert_users" min-width="120" />
                <el-table-column label="曝光量" prop="exposure" min-width="120" />
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['marketing.operate_case.lists/status']"
                            @change="changeStatus(row)"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0" />
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" show-overflow-tooltip min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['marketing.operate_case.lists/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['marketing.operate_case.lists/delete']"
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
        <edit-popup v-if="showEdit" ref="editRef" @success="getLists" @close="showEdit = false" />
    </div>
</template>
<script lang="ts" setup name="problemExample">
import { usePaging } from "@/hooks/usePaging";
import { getOperateCaseList, editOperateCase, deleteOperateCase } from "@/api/marketing/operate_case";
import feedback from "@/utils/feedback";
import EditPopup from "./edit.vue";

const editRef = shallowRef<InstanceType<typeof EditPopup>>();
//搜索参数
const queryParams = reactive({
    title: "",
    status: "",
    start_time: "",
    end_time: "",
});
const showEdit = ref(false);
//添加
const handleAdd = async () => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("add");
};

//编辑
const handleEdit = async (data: any) => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("edit");
    editRef.value?.setFormData(data);
};

//删除
const handleDelete = async (id: number) => {
    await feedback.confirm("确定要删除？");
    await deleteOperateCase({ id });
    getLists();
};

//修改状态
const changeStatus = async (row: any) => {
    try {
        await editOperateCase({ id: row.id, status: row.status });
    } finally {
        getLists();
    }
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getOperateCaseList,
    params: queryParams,
});

getLists();
</script>
