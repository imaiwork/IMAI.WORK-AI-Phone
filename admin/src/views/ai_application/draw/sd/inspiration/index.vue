<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[280px]" label="模型名称">
                    <el-input v-model="queryParams.title" placeholder="请输入名称" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex gap-2">
                <router-link
                    v-perms="['ai_application.sd.inspiration/add']"
                    :to="getRoutePath('ai_application.sd.inspiration/add:edit')">
                    <el-button type="primary">
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        新增
                    </el-button>
                </router-link>
            </div>
            <el-table size="large" class="mt-4" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="封面" min-width="100">
                    <template #default="{ row }">
                        <image-contain
                            :src="row.pic"
                            :width="50"
                            :height="50"
                            :preview-src-list="[row.pic]"
                            preview-teleported
                            fit="cover" />
                    </template>
                </el-table-column>
                <el-table-column label="描述" prop="title" min-width="160" show-overflow-tooltip />
                <el-table-column label="所属分类" prop="category.title" min-width="120" />
                <el-table-column label="状态" min-width="100" v-perms="['ai_application.sd.inspiration/status']">
                    <template #default="{ row }">
                        <el-switch
                            @change="changeStatus(row.id)"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0" />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" min-width="120" />
                <el-table-column label="创建时间" prop="create_time" show-overflow-tooltip min-width="180" />
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link>
                            <router-link
                                v-perms="['ai_application.sd.inspiration/edit']"
                                :to="{
                                    path: getRoutePath('ai_application.sd.inspiration/add:edit'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                编辑
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.sd.inspiration/del']"
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
</template>
<script lang="ts" setup name="problemExample">
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import { getModelList, delModel, editModelStatus } from "@/api/ai_application/draw/draw_sd";
import feedback from "@/utils/feedback";

const route = useRoute();
//搜索参数
const queryParams: {
    title: string;
    status: string;
    category_id: number | string;
} = reactive({
    title: "",
    status: "",
    category_id: "",
});
const multipleSelection = ref<any[]>([]);

queryParams.category_id = parseInt(route.query.category_id as string) || "";

//删除
const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除？");
    await delModel({ id });
    getLists();
};

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

//修改状态
const changeStatus = async (id: any) => {
    try {
        await editModelStatus({ id });
    } finally {
        getLists();
    }
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getModelList,
    params: queryParams,
});

getLists();
</script>
