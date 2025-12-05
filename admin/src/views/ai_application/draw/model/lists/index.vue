<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
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
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4">
                <el-button v-perms="['ai_application.draw_model.lists/add']" type="primary" @click="handleAdd"
                    >新增</el-button
                >
            </div>
            <el-table :data="pager.lists" v-loading="pager.loading">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column label="模特">
                    <template #default="{ row }">
                        <div class="w-full flex gap-2">
                            <image-contain
                                :src="row.result_image"
                                :width="50"
                                :height="50"
                                :preview-src-list="[row.result_image]"
                                preview-teleported
                                fit="cover" />
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="140">
                    <template #default="{ row }">
                        <el-tag :type="row.status == '1' ? 'success' : 'danger'" effect="plain">
                            {{ row.status == "1" ? "开启" : "关闭" }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="创建人" min-width="120">
                    <template #default="{ row }">
                        <div v-if="row.user_id == 0">管理员</div>
                        <div v-else>
                            {{ row.nickname }}
                        </div>
                    </template>
                </el-table-column>
                <el-table-column prop="create_time" label="创建时间" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['ai_application.draw_model.lists/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                            >编辑</el-button
                        >
                        <el-button
                            v-perms="['ai_application.draw_goods.lists/delete']"
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
import { getDrawCaseList, delDrawCase } from "@/api/ai_application/draw/draw_case";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import EditPopup from "./edit.vue";
import { cloneDeep } from "lodash-es";

const editRef = shallowRef<InstanceType<typeof EditPopup>>();

const showEdit = ref(false);
const queryParams = reactive({
    status: "",
    case_type: [4],
});
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getDrawCaseList,
    params: queryParams,
});

const handleAdd = async () => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("add");
};

const handleEdit = async (row: any) => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("edit", cloneDeep(row));
};

const handleDel = async (id: string) => {
    await feedback.confirm("确定要删除吗？");
    await delDrawCase({ id });
    getLists();
};

getLists();
</script>

<style scoped></style>
