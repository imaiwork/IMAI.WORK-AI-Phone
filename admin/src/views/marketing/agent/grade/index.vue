<template>
    <el-card shadow="never" class="!border-none">
        <el-table :data="gradeList" style="width: 100%">
            <el-table-column prop="key" label="代理等级">
                <template #default="{ row }">
                    <div>{{ row.level }}级</div>
                </template>
            </el-table-column>
            <el-table-column prop="name" label="等级名称" />
            <el-table-column label="操作" width="100">
                <template #default="{ row }">
                    <el-button type="primary" link @click="handleEdit(row)"> 编辑 </el-button>
                </template>
            </el-table-column>
        </el-table>
    </el-card>

    <EditPopup v-if="showEditPopup" ref="editPopupRef" @success="getConfig" @close="showEditPopup = false" />
</template>

<script setup lang="ts">
import { getAgentGradeConfig } from "@/api/marketing/agent";
import EditPopup from "./edit.vue";

const showEditPopup = ref(false);

const gradeList = ref<any[]>([]);

const editPopupRef = ref<InstanceType<typeof EditPopup>>();

const handleEdit = async (row: any) => {
    showEditPopup.value = true;
    await nextTick();
    editPopupRef.value?.open(gradeList.value);
    editPopupRef.value?.setFormData(row);
};

// 编辑成功后刷新列表
const getConfig = async () => {
    const res = await getAgentGradeConfig();
    gradeList.value = res;
};

getConfig();
</script>
