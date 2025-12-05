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
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4">
                <el-button v-perms="['ai_application.draw_model.case/add']" type="primary" @click="handleAdd"
                    >新增</el-button
                >
            </div>
            <div>
                <el-tabs v-model="caseType" @tab-click="changeCaseType">
                    <el-tab-pane label="模特图案例" :name="0" />
                    <el-tab-pane label="商品图案例" :name="3" />
                </el-tabs>
            </div>
            <el-table :data="pager.lists" v-loading="pager.loading">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column
                    prop="params.text"
                    label="描述"
                    min-width="200"
                    show-overflow-tooltip
                    v-if="caseType == 3" />
                <el-table-column label="类型" width="120" v-if="caseType == 0">
                    <template #default="{ row }">
                        <div v-if="row.case_type == 0">上下衣</div>
                        <div v-if="row.case_type == 1">连衣裙</div>
                    </template>
                </el-table-column>
                <el-table-column label="图片" min-width="200" v-if="caseType == 0">
                    <template #default="{ row }">
                        <div class="w-full flex gap-2">
                            <image-contain
                                :src="row.params.images[0]"
                                :width="50"
                                :height="50"
                                :preview-src-list="[row.params.images[0]]"
                                preview-teleported
                                fit="cover" />
                            <image-contain
                                :src="row.params.images[1]"
                                :width="50"
                                :height="50"
                                :preview-src-list="[row.params.images[1]]"
                                preview-teleported
                                fit="cover" />
                            <image-contain
                                v-if="row.case_type == 0"
                                :src="row.params.images[2]"
                                :width="50"
                                :height="50"
                                :preview-src-list="[row.params.images[2]]"
                                preview-teleported
                                fit="cover" />
                        </div>
                    </template>
                </el-table-column>
                <el-table-column prop="result_image" label="生成图" min-width="120">
                    <template #default="{ row }">
                        <image-contain
                            :src="row.result_image"
                            :width="50"
                            :height="50"
                            :preview-src-list="[row.result_image]"
                            preview-teleported
                            fit="cover" />
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="140">
                    <template #default="{ row }">
                        <el-tag :type="row.status == '1' ? 'success' : 'danger'" effect="plain">
                            {{ row.status == "1" ? "开启" : "关闭" }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="create_time" label="创建时间" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['ai_application.draw_goods.case/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                            >编辑</el-button
                        >
                        <el-button
                            v-perms="['ai_application.draw_goods.case/delete']"
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
    <edit-popup v-if="showEdit" ref="editRef" :case-type="caseType" @close="showEdit = false" @success="getLists" />
</template>

<script setup lang="ts">
import { getDrawCaseList, delDrawCase } from "@/api/ai_application/draw/draw_case";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import EditPopup from "./edit.vue";
import { cloneDeep } from "lodash-es";

const editRef = shallowRef<InstanceType<typeof EditPopup>>();

const caseType = ref(0);

const showEdit = ref(false);
const queryParams = reactive<any>({
    status: "",
    case_type: [0, 1],
});
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getDrawCaseList,
    params: queryParams,
});

const changeCaseType = () => {
    if (caseType.value == 0) {
        queryParams.case_type = [0, 1];
    } else {
        queryParams.case_type = [3];
    }
    getLists();
};

const handleReset = () => {
    if (caseType.value == 0) {
        queryParams.case_type = [0, 1];
    } else {
        queryParams.case_type = [3];
    }
    queryParams.status = "";
    getLists();
};

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
