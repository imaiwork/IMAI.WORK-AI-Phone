<template>
    <div>
        <el-card shadow="never" class="!border-none">
            <div v-perms="['marketing.agent.package/add']" @click="handleAdd">
                <el-button type="primary">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增充值套餐
                </el-button>
            </div>
            <el-table size="large" :data="pager.lists" v-loading="pager.loading" class="mt-4">
                <el-table-column label="套餐名称" prop="name" min-width="120"></el-table-column>
                <el-table-column label="算力值" prop="tokens" min-width="120"> </el-table-column>
                <el-table-column label="排序" prop="sort"> </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            type="primary"
                            link
                            v-perms="['marketing.agent.package/edit']"
                            @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['marketing.agent.package/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>
    </div>
    <edit-popup v-if="showEditPopup" ref="editPopupRef" @success="getLists" @close="showEditPopup = false" />
</template>
<script setup lang="ts">
import { getAgentPackageList, deleteAgentPackage } from "@/api/marketing/agent";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import EditPopup from "./edit.vue";

const showEditPopup = ref(false);
const editPopupRef = ref();

const { pager, getLists } = usePaging({
    fetchFun: getAgentPackageList,
});

//删除
const handleDelete = async (id: number) => {
    await feedback.confirm("确定要删除？");
    await deleteAgentPackage({ id });
    getLists();
};

const handleAdd = async () => {
    showEditPopup.value = true;
    await nextTick();
    editPopupRef.value?.open("add");
};

const handleEdit = async (row: any) => {
    showEditPopup.value = true;
    await nextTick();
    editPopupRef.value?.open("edit");
    editPopupRef.value?.setFormData(row);
};

getLists();
</script>
