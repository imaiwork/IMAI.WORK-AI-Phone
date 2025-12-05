<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="类别名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入类别名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="类型">
                    <el-select
                        v-model="queryParams.type"
                        class="!w-[180px]"
                        placeholder="请选择类型"
                        @change="resetPage">
                        <el-option label="AI智能体" :value="1"></el-option>
                        <el-option label="Coze智能体" :value="2"></el-option>
                        <el-option label="Coze工作流" :value="3"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex justify-between">
                <el-button v-perms="['ai_application.agent.cate/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增
                </el-button>
            </div>
            <el-table size="large" class="mt-4" v-loading="pager.loading" row-key="id" :data="pager.lists">
                <el-table-column label="类别名称" prop="name" min-width="180" />
                <el-table-column label="类型" prop="type" min-width="160">
                    <template #default="{ row }">
                        {{ getTypeName(row.type) }}
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['ai_application.agent.cate/status']"
                            @change="changeStatus(row.id)"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0" />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="创建时间" prop="create_time" show-overflow-tooltip min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['ai_application.agent.cate/status']"
                            type="primary"
                            link
                            @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['ai_application.agent.cate/delete']"
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
<script lang="ts" setup>
import { getCateList, deleteCate, changeStatus as changeStatusApi } from "@/api/ai_application/agent/cate";
import { usePaging } from "@/hooks/usePaging";
import EditPopup from "./edit.vue";
import feedback from "@/utils/feedback";
const editRef = shallowRef<InstanceType<typeof EditPopup>>();
//搜索参数
const queryParams = reactive({
    name: "",
    type: "",
});
const showEdit = ref(false);

//获取类型名称
const getTypeName = (type: number) => {
    return type == 1 ? "AI智能体" : type == 2 ? "Coze智能体" : "Coze工作流";
};

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
    await deleteCate({ id });
    getLists();
};

//修改状态
const changeStatus = async (id: any) => {
    try {
        await changeStatusApi({ id });
    } finally {
        getLists();
    }
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getCateList,
    params: queryParams,
});

getLists();
</script>
