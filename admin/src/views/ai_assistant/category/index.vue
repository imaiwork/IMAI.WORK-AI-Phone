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
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex justify-between">
                <el-button v-perms="['ai_assistant.category/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增
                </el-button>
                <el-button
                    v-if="isImport"
                    v-perms="['ai_assistant.category/import']"
                    type="primary"
                    @click="handleImport"
                    >导入分类</el-button
                >
            </div>
            <el-table
                size="large"
                class="mt-4"
                v-loading="pager.loading"
                row-key="id"
                :data="pager.lists"
                :tree-props="{ children: 'sub_list' }">
                <el-table-column label="类别名称" prop="name" min-width="180" />
                <el-table-column label="类别logo" prop="logo" min-width="120">
                    <template #default="{ row }">
                        <el-image v-if="row.logo" :src="row.logo" class="w-[44px] h-[44px]" fit="cover" />
                    </template>
                </el-table-column>
                <el-table-column label="类型描述" prop="description" min-width="160" />
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['ai_assistant.category/status']"
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
                            v-perms="['ai_assistant.category/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['ai_assistant.category/delete']"
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
import EditPopup from "./edit.vue";
import {
    getAssistantCategoryList,
    assistantCategoryDelete,
    assistantCategoryStatus,
    assistantCategoryImport,
    assistantCategoryImportCheck,
} from "@/api/ai_assistant/category";
import feedback from "@/utils/feedback";
const editRef = shallowRef<InstanceType<typeof EditPopup>>();
//搜索参数
const queryParams = reactive({
    name: "",
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
    await assistantCategoryDelete({ id });
    getLists();
};

//修改状态
const changeStatus = async (id: any) => {
    try {
        await assistantCategoryStatus({ id });
    } finally {
        getLists();
    }
};

const handleImport = async () => {
    await feedback.confirm("确定要导入？");
    feedback.loading("导入中...");

    try {
        await assistantCategoryImport();
        getLists();
        feedback.msgSuccess("导入成功");
    } finally {
        feedback.closeLoading();
    }
};

const isImport = ref(false);
const importCheck = async () => {
    const data = await assistantCategoryImportCheck();
    isImport.value = data.import;
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getAssistantCategoryList,
    params: queryParams,
});

importCheck();
getLists();
</script>
